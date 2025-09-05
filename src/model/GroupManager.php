<?php

namespace MohamadRZ\NovaPerms\model;


use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

class GroupManager {
    const DEFAULT_GROUP = "default";
    /** @var array<string,Group> */
    private array $groups = [];

    public function loadDefaults(): void
    {
        NovaPermsPlugin::getStorage()->loadAllGroup();
        if (!$this->getGroup(self::DEFAULT_GROUP)) {
            NovaPermsPlugin::getStorage()->createAndLoadGroup(self::DEFAULT_GROUP);
        }
    }

    public function getOrMake($name): Group
    {
        if ($this->getGroup($name)) {
            return $this->getGroup($name);
        } else {
            return new Group($name);
        }
    }

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
