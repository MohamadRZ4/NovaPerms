<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\Types\PermissionNodeBuilder;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use pocketmine\permission\PermissionManager;

abstract class PermissionHolder
{
    private array $permissions = [];

    /** @var InheritanceNode[] */
    protected array $inheritances = [];

    public function addPermission(AbstractNode|string $node, bool $value = true): void
    {
        if (!$node instanceof AbstractNode) {
            $node = (new PermissionNodeBuilder($node))->value($value)->build();
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

    public function getAllKnownPermissions(): array
    {
        $list = [];

        foreach (PermissionManager::getInstance()->getPermissions() as $perm) {
            $list[] = $perm->getName();
        }

        return $list;
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
