<?php

namespace MohamadRZ\NovaPerms\model;


class GroupManager
{
    /** @var array<string,Group> */
    private array $groups = [];

    public function registerGroup(Group $group): void
    {
        $this->groups[strtolower($group->getName())] = $group;
    }

    public function getGroup(string $name): ?Group
    {
        return $this->groups[strtolower($name)] ?? null;
    }

    public function removeGroup(string $name): void
    {
        unset($this->groups[strtolower($name)]);
    }

    public function getAllGroups(): array
    {
        return array_values($this->groups);
    }

    public function clearGroups(): void
    {
        $this->groups = [];
    }
}
