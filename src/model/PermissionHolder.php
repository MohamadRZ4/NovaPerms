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

    // ==================== Permission Management ====================

    public function addPermission(AbstractNode|string|array $nodes, bool $value = true): void
    {
        $nodesList = is_array($nodes) ? $nodes : [$nodes];

        foreach ($nodesList as $node) {
            if (!$node instanceof AbstractNode) {
                $deserialized = NodeDeserializer::deserialize([$node]);
                if (empty($deserialized)) continue;
                $node = $deserialized[0];
                $node = $node->toBuilder()->value($value)->build();
            }

            if ($node instanceof InheritanceNode) {
                $this->addInheritance($node);
            }

            $storageKey = $node->getKey() . ($node->isNegated() ? ':negated' : '');
            $this->permissions[$storageKey] = $node;
        }
    }

    public function removePermission(AbstractNode|string|array $nodes): bool
    {
        $removed = false;
        $nodesList = is_array($nodes) ? $nodes : [$nodes];

        foreach ($nodesList as $node) {
            if (is_string($node)) {
                $deserialized = NodeDeserializer::deserialize([$node]);
                if (empty($deserialized)) {
                    continue;
                }
                $node = $deserialized[0];
            }

            if (!$node instanceof AbstractNode) {
                continue;
            }

            $storageKey = $node->getKey() . ($node->isNegated() ? ':negated' : '');

            if (!isset($this->permissions[$storageKey])) {
                continue;
            }

            $existing = $this->permissions[$storageKey];

            if ($existing instanceof InheritanceNode) {
                $this->removeInheritance($existing);
            }

            unset($this->permissions[$storageKey]);
            $removed = true;
        }

        return $removed;
    }

    public function hasPermission(AbstractNode|string $node): bool
    {
        $name = is_string($node) ? $node : $node->getKey();
        return isset($this->permissions[$name]) && $this->permissions[$name]->getValue() === true;
    }

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

    // ==================== Temporary Permissions ====================

    public function setTempPermission(User|Group $holder, string|AbstractNode|array $nodeInput, bool $value = true, int $durationSeconds = 3600, string $modifier = 'replace'): bool
    {
        $now = time();
        $expiry = $now + $durationSeconds;

        if (is_string($nodeInput) || is_array($nodeInput)) {
            $nodes = NodeDeserializer::deserialize(
                is_array($nodeInput) ? $nodeInput : [$nodeInput]
            );
            if (empty($nodes)) return false;
            $node = $nodes[0];
        } else {
            $node = $nodeInput;
        }

        /** @var AbstractNode $node */
        $builder = $node->toBuilder();
        $builder->value($value);

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
                    $builder->expiry($oldExpiry + $durationSeconds);
                } else {
                    $builder->expiry($expiry);
                }
                break;

            case 'replace':
                if ($existingNode) {
                    $oldExpiry = $existingNode->getExpiry() !== -1 ? $existingNode->getExpiry() : $expiry;
                    $builder->expiry(max($expiry, $oldExpiry));
                    $this->removePermission($existingNode);
                } else {
                    $builder->expiry($expiry);
                }
                break;

            case 'deny':
                if ($existingNode) {
                    return false;
                }
                $builder->expiry($expiry);
                break;

            default:
                $builder->expiry($expiry);
                break;
        }

        $this->addPermission($builder->build());

        if ($holder instanceof User) {
            $holder->updatePermissions();
        }

        return true;
    }

    public function unsetTempPermission(User|Group $holder, string|AbstractNode|array $nodeInput, ?int $durationSeconds = null): bool
    {
        if (is_string($nodeInput) || is_array($nodeInput)) {
            $nodes = NodeDeserializer::deserialize(
                is_array($nodeInput) ? $nodeInput : [$nodeInput]
            );
            if (empty($nodes)) return false;
            $node = $nodes[0];
        } else {
            $node = $nodeInput;
        }

        $found = false;
        $now = time();

        foreach ($this->getOwnPermissionNodes() as $existing) {
            if ($existing->getKey() === $node->getKey()) {
                $found = true;
            }
        }

        if (!$found) return false;

        if ($durationSeconds !== null) {
            $denyNode = (new PermissionNodeBuilder($node->getKey()))
                ->value(false)
                ->negated(true)
                ->expiry($now + $durationSeconds)
                ->build();
            $holder->addPermission($denyNode);
        } else {
            $this->removePermission($node->getKey());
        }

        if ($holder instanceof User) {
            $holder->updatePermissions();
        }

        return true;
    }

    public function auditTemporaryNodes(): bool
    {
        $changed = false;
        $now = time();

        foreach ($this->getOwnPermissionNodes() as $node) {
            if ($node->getExpiry() === -1) {
                continue;
            }

            if ($now < $node->getExpiry()) {
                continue;
            }

            $this->removePermission($node);
            $changed = true;
        }

        return $changed;
    }

    // ==================== Getters & Setters ====================

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

    // ==================== Inheritance Management ====================

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

    // ==================== Static Group Update Methods ====================

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
}