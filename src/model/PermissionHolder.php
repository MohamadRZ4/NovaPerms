<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\Node;
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

    public function addPermission(Node $node, bool $value = true): void
    {
        $node = $node->toBuilder()
            ->value($value)
            ->build();

        if ($node instanceof InheritanceNode) {
            $this->addInheritance($node);
        }

        $storageKey = $this->makeStorageKey($node);
        $this->permissions[$storageKey] = $node;
    }

    public function removePermission(Node $node): bool
    {
        $removed = false;

        foreach ($this->permissions as $key => $existing) {
            if (
                $existing->getKey() === $node->getKey() &&
                $existing->getExpiry() === -1
            ) {
                if ($existing instanceof InheritanceNode) {
                    $this->removeInheritance($existing);
                }

                unset($this->permissions[$key]);
                $removed = true;
            }
        }

        return $removed;
    }

    public function setTempPermission(
        User|Group                 $holder,
        Node                       $node,
        bool                       $value = true,
        int                        $durationSeconds = 3600,
        TemporaryNodeMergeStrategy $modifier = TemporaryNodeMergeStrategy::REPLACE
    ): bool {
        $now = time();
        $expiry = $now + $durationSeconds;

        $builder = $node->toBuilder()
            ->value($value);

        $existingNode = null;

        foreach ($this->getOwnPermissionNodes() as $existing) {
            if ($existing->getKey() === $node->getKey()) {
                $existingNode = $existing;
                break;
            }
        }

        switch ($modifier) {

            case TemporaryNodeMergeStrategy::ACCUMULATE:
                if ($existingNode) {
                    $oldExpiry = $existingNode->getExpiry() !== -1
                        ? $existingNode->getExpiry()
                        : $expiry;

                    $builder->expiry($oldExpiry + $durationSeconds);
                } else {
                    $builder->expiry($expiry);
                }
                break;

            case TemporaryNodeMergeStrategy::REPLACE:
                if ($existingNode) {
                    $oldExpiry = $existingNode->getExpiry() !== -1
                        ? $existingNode->getExpiry()
                        : $expiry;

                    $builder->expiry(max($expiry, $oldExpiry));
                    $this->removePermission($existingNode);
                } else {
                    $builder->expiry($expiry);
                }
                break;

            case TemporaryNodeMergeStrategy::DENY:
                $builder->expiry($expiry);
                break;
        }

        $this->addPermission($builder->build());

        if ($holder instanceof User) {
            $holder->updatePermissions();
        }

        return true;
    }

    public function unsetTempPermission(
        User|Group $holder,
        Node       $node,
        ?int       $durationSeconds = null
    ): bool {
        $now = time();
        $changed = false;

        foreach ($holder->getOwnPermissionNodes() as $existing) {

            if ($existing->getKey() !== $node->getKey()) {
                continue;
            }

            if ($existing->getExpiry() === -1) {
                continue;
            }

            if ($durationSeconds === null) {
                $holder->removePermission($existing);
                $changed = true;
                continue;
            }

            $newExpiry = $existing->getExpiry() - $durationSeconds;

            if ($newExpiry <= $now) {
                $holder->removePermission($existing);
            } else {
                $newNode = $existing->toBuilder()
                    ->expiry($newExpiry)
                    ->build();

                $holder->removePermission($existing);
                $holder->addPermission($newNode);
            }

            $changed = true;
        }

        if ($changed && $holder instanceof User) {
            $holder->updatePermissions();
        }

        return $changed;
    }

    public function findPermissionNode(Node|string $nodeInput): ?Node
    {
        $key = $nodeInput instanceof Node ? $nodeInput->getKey() : $nodeInput;

        foreach ($this->getOwnPermissionNodes() as $node) {
            if ($node->getKey() === $key) {
                return $node;
            }
        }

        return null;
    }

    private function makeStorageKey(Node $node): string
    {
        if ($node->getExpiry() === -1) {
            return $node->getKey();
        }

        return $node->getKey() . '#temp@' . $node->getExpiry();
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
     * @return array<string, Node>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return Node[]
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

            $group = NovaPermsPlugin::getGroupManager()->getIfLoaded($current);
            if ($group !== null) {
                foreach ($group->getInheritances() as $inheritNode) {
                    $stack[] = $inheritNode->getGroup();
                }
            }
        }

        return false;
    }
}