<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\AbstractNode;

class Group extends PermissionHolder
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addPermission(AbstractNode|string|array $nodes, bool $value = true): void
    {
        parent::addPermission($nodes, $value);

        $this->updateUsersForGroup($this->getName());
    }

    public function removePermission(AbstractNode|string $node): bool
    {
        $isRemoved = parent::removePermission($node);

        if ($isRemoved) {
            $this->updateUsersForGroup($this->getName());
        }

        return $isRemoved;
    }

}
