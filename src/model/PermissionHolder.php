<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\Types\PermissionNodeBuilder;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;

abstract class PermissionHolder
{
    private array $permissions = [];

    /** @var InheritanceNode[] */
    protected array $inheritances = [];

    public function addPermission(AbstractNode|string $node, bool $value = true): void
    {
        if (!$node instanceof AbstractNode) {
            $node = new PermissionNodeBuilder($node)->value($value)->build();
        } elseif ($node instanceof InheritanceNode) {
            $this->addInheritance($node);
        }
        $this->permissions[$node->getKey()] = $node;
    }

    public function removePermission(AbstractNode|string $node): void
    {
        $name = is_string($node) ? $node : $node->getKey();
        if ($node instanceof InheritanceNode) {
            $this->removeInheritance($node);
        }
        unset($this->permissions[$name]);
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

    public function getAllInheritancePermissions(GroupManager $manager, array &$visited = []): array
    {
        $id = spl_object_id($this);
        if (isset($visited[$id])) {
            return [];
        }
        $visited[$id] = true;

        $perms = $this->permissions;

        foreach ($this->inheritances as $inherit) {
            $parentGroup = $manager->getGroup($inherit->getGroup());
            if ($parentGroup !== null) {
                $perms += $parentGroup->getAllInheritancePermissions($manager, $visited);
            }
        }

        return $perms;
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
}
