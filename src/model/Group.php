<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\Node;

class Group extends PermissionHolder
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addPermission(Node|string|array $nodes, bool $value = true): void
    {
        parent::addPermission($nodes, $value);

        $this->updateUsersForGroup($this->getName());
    }

    public function removePermission(Node|array|string $nodes): bool
    {
        $isRemoved = parent::removePermission($nodes);

        if ($isRemoved) {
            $this->updateUsersForGroup($this->getName());
        }

        return $isRemoved;
    }

}
