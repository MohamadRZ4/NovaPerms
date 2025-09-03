<?php

namespace MohamadRZ\NovaPerms\model;

class Group extends PermissionHolder
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
