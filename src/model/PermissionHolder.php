<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\serialize\NodeDeserializer;
use MohamadRZ\NovaPerms\node\Types\PermissionNodeBuilder;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\permission\PermissionManager;

abstract class PermissionHolder
{
    private array $permissions = [];

    /** @var InheritanceNode[] */
    protected array $inheritances = [];

    public function addPermission(AbstractNode|string|array $nodes, bool $value = true): void
    {
        $nodesList = is_array($nodes) ? $nodes : [$nodes];

        foreach ($nodesList as $node) {
            if (!$node instanceof AbstractNode) {
                $deserialized = NodeDeserializer::deserialize([$node]);
                if (empty($deserialized)) continue;
                $node = $deserialized[0];
                /* @var $node AbstractNode */
                $node->toBuilder()->value($value)->build();
            }

            if ($node instanceof InheritanceNode) {
                $this->addInheritance($node);
            }

            $this->permissions[$node->getKey()] = $node;
        }
    }

    public function removePermission(AbstractNode|string $node): bool
    {
        if (is_string($node)) {
            $nodes = NodeDeserializer::deserialize([$node]);

            if (empty($nodes)) {
                return false;
            }

            $node = $nodes[0];
        }

        $name = $node->getKey();

        if (!isset($this->permissions[$name])) {
            return false;
        }

        if ($this->permissions[$name] instanceof InheritanceNode) {
            $this->removeInheritance($this->permissions[$name]);
        }

        unset($this->permissions[$name]);
        return true;
    }

    public function auditTemporaryNodes(): bool
    {
        $nodes = $this->getOwnPermissionNodes();
        $changed = false;

        foreach ($nodes as $node) {
            if ($node->getExpiry() !== -1 && time() >= $node->getExpiry()) {
                $this->removePermission($node);
                $changed = true;
            }
        }

        return $changed;
    }

    public function hasPermission(AbstractNode|string $node): bool
    {
        $name = is_string($node) ? $node : $node->getKey();
        return isset($this->permissions[$name]) && $this->permissions[$name]->getValue() === true;
    }

    /**
     * @return array<string, AbstractNode>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return AbstractNode[]
     */
    public function getOwnPermissionNodes(): array
    {
        return array_values($this->permissions);
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    private function addInheritance(InheritanceNode $node): void
    {
        $this->inheritances[] = $node;
    }

    private function removeInheritance(InheritanceNode $targetNode): void
    {
        foreach ($this->inheritances as $index => $node) {
            if ($node === $targetNode) {
                unset($this->inheritances[$index]);
                break;
            }
        }
    }

    /**
     * @return InheritanceNode[]
     */
    public function getInheritances(): array
    {
        return array_values($this->inheritances);
    }

    public static function updateUsersForGroup(string $changedGroupName): void
    {
        $allUsers = NovaPermsPlugin::getUserManager()->getAllUsers();

        foreach ($allUsers as $user) {
            if (self::userHasGroupInheritance($user, $changedGroupName)) {
                $user->updatePermissions();
            }
        }
    }

    private static function userHasGroupInheritance(User $user, string $groupName): bool
    {
        $checked = [];
        $stack = $user->getGroups();

        while (!empty($stack)) {
            $current = array_pop($stack);

            if ($current === $groupName) return true;
            if (isset($checked[$current])) continue;

            $checked[$current] = true;

            $group = NovaPermsPlugin::getGroupManager()->getGroup($current);
            if ($group !== null) {
                foreach ($group->getInheritances() as $inheritNode) {
                    $stack[] = $inheritNode->getGroup();
                }
            }
        }

        return false;
    }


    public function setTempPermission(User|Group $holder, string|AbstractNode|array $nodeInput, bool $value = true, int $durationSeconds = 3600, string $modifier = 'replace'): bool {
        $now = time();
        $expiry = $now + $durationSeconds;

        if (is_string($nodeInput) || is_array($nodeInput)) {
            $nodes = NodeDeserializer::deserialize([$nodeInput]);
            if (empty($nodes)) return false;
            $node = $nodes[0];
        } else {
            $node = $nodeInput;
        }

        $node->setValue($value);

        $existingNodes = $this->getOwnPermissionNodes();
        $existingNode = null;

        foreach ($existingNodes as $n) {
            if ($n->getKey() === $node->getKey()) {
                $existingNode = $n;
                break;
            }
        }

        switch ($modifier) {
            case 'accumulate':
                if ($existingNode) {
                    $oldExpiry = $existingNode->getExpiry() !== -1 ? $existingNode->getExpiry() : $expiry;
                    $node->setExpiry($oldExpiry + $durationSeconds);
                } else {
                    $node->setExpiry($expiry);
                }
                break;

            case 'replace':
                if ($existingNode) {
                    $oldExpiry = $existingNode->getExpiry() !== -1 ? $existingNode->getExpiry() : $expiry;
                    $node->setExpiry(max($expiry, $oldExpiry));
                    $this->removePermission($existingNode);
                } else {
                    $node->setExpiry($expiry);
                }
                break;

            case 'deny':
                if ($existingNode) {
                    return false;
                }
                $node->setExpiry($expiry);
                break;

            default:
                $node->setExpiry($expiry);
                break;
        }

        $this->addPermission($node);

        if ($holder instanceof User) {
            $holder->updatePermissions();
        }

        return true;
    }

    /**
     * Removes a temporary permission from this PermissionHolder (User or Group).
     *
     * @param User|Group $holder
     * @param AbstractNode|string|array $nodeInput Node, node string, or serialized node array
     * @param int|null $durationSeconds Optional: temporarily deny this permission for $durationSeconds
     * @return bool True if a matching temporary permission was found and removed, false otherwise
     */
    public function unsetTempPermission(User|Group $holder, string|AbstractNode|array $nodeInput, ?int $durationSeconds = null): bool {
        if (is_string($nodeInput) || is_array($nodeInput)) {
            $nodes = NodeDeserializer::deserialize([$nodeInput]);
            if (empty($nodes)) return false;
            $node = $nodes[0];
        } else {
            $node = $nodeInput;
        }

        $found = false;
        $now = time();
        foreach ($this->getOwnPermissionNodes() as $existing) {
            if ($existing->getKey() === $node->getKey() && $existing->getExpiry() !== -1) {
                $found = true;
                $this->removePermission($existing);
            }
        }

        if (!$found) return false;

        if ($durationSeconds !== null && $holder instanceof User) {
            $denyNode = (new PermissionNodeBuilder($node->getKey()))
                ->value(false)
                ->negated(true)
                ->expiry($now + $durationSeconds)
                ->build();
            $holder->addPermission($denyNode);
        }

        if ($holder instanceof User) {
            $holder->updatePermissions();
        }

        return true;
    }

    /**
     * Searches for a permission node in this PermissionHolder.
     *
     * @param AbstractNode|string $nodeInput Node key or AbstractNode
     * @return AbstractNode|null Returns the found node, or null if not found
     */
    public function findPermissionNode(AbstractNode|string $nodeInput): ?AbstractNode
    {
        $key = $nodeInput instanceof AbstractNode ? $nodeInput->getKey() : $nodeInput;

        foreach ($this->getOwnPermissionNodes() as $node) {
            if ($node->getKey() === $key) {
                return $node;
            }
        }

        return null;
    }

}
