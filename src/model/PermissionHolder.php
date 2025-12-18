<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\AbstractNode;
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
                $node = (new PermissionNodeBuilder($node))->value($value)->build();
            }

            if ($node instanceof InheritanceNode) {
                $this->addInheritance($node);
            }

            $this->permissions[$node->getKey()] = $node;
        }
    }

    public function removePermission(AbstractNode|string $node): void
    {
        $name = is_string($node) ? $node : $node->getKey();
        if ($node instanceof InheritanceNode) {
            $this->removeInheritance($node);
        }
        unset($this->permissions[$name]);
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
}
