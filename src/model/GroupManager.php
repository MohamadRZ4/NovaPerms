<?php

namespace MohamadRZ\NovaPerms\model;


use MohamadRZ\NovaPerms\node\Types\DisplayNameNode;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\node\Types\WeightNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

class GroupManager {
    const DEFAULT_GROUP = "default";
    /** @var array<string,Group> */
    private array $groups = [];

    public function init()
    {
        NovaPermsPlugin::getStorage()->loadAllGroup();
        if (!$this->getGroup(self::DEFAULT_GROUP)) {
            NovaPermsPlugin::getStorage()->createAndLoadGroup(self::DEFAULT_GROUP);
        }
    }

    public function getOrMake($name): Group
    {
        $name = strtolower($name);
        return $this->groups[$name] ?? $this->groups[$name] = new Group($name);
    }

    public function getGroup($name): ?Group
    {
        return $this->groups[strtolower($name)] ?? null;
    }

    public function createGroup(string $name, int $weight = -1, string $displayName = ""): bool
    {
        $name = trim($name);
        $displayName = trim($displayName);

        foreach ($this->getAllGroups() as $group) {
            if (strcasecmp($name, $group->getName()) === 0) {
                return false;
            }
        }

        $storage = NovaPermsPlugin::getStorage();
        $group = $this->getOrMake($name);

        if ($weight !== -1) {
            $group->addPermission(WeightNode::builder($weight)->build());
        }

        if ($displayName !== "") {
            $group->addPermission(DisplayNameNode::builder($displayName)->build());
        }

        $this->registerGroup($group);
        $storage->saveGroup($group);
        return true;
    }

    public function registerGroup(Group $group): void
    {
        $this->groups[strtolower($group->getName())] = $group;
    }

    public function removeGroup(string $name): void
    {
        unset($this->groups[strtolower($name)]);
    }

    /** @return Group[] */
    public function getAllGroups(): array
    {
        return array_values($this->groups);
    }

    public function clearGroups(): void
    {
        $this->groups = [];
    }
}
