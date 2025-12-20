<?php

namespace MohamadRZ\NovaPerms\model;


use MohamadRZ\NovaPerms\node\Types\DisplayNameNode;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\node\Types\WeightNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;

class GroupManager {
    const DEFAULT_GROUP = "default";
    /** @var array<string,Group> */
    private array $groups = [];

    public function init()
    {
/*        NovaPermsPlugin::getStorage()->loadAllGroup();
        if (!$this->getGroup(self::DEFAULT_GROUP)) {
            NovaPermsPlugin::getStorage()->createAndLoadGroup(self::DEFAULT_GROUP);
        }*/
        $this->createGroup(self::DEFAULT_GROUP);
    }

    public function getOrMake($name): Group
    {
        $name = strtolower($name);
        return $this->groups[$name] ?? $this->groups[$name] = new Group($name);
    }

    public function getIfLoaded($name): ?Group
    {
        return $this->groups[strtolower($name)] ?? null;
    }

    public function createGroup(string $name, int $weight = -1, ?string $displayName = null): bool
    {
        $name = trim($name);

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

        if ($displayName !== null) {
            $displayName = trim($displayName);
            $group->addPermission(DisplayNameNode::builder($displayName)->build());
        }

        $this->registerGroup($group);
        $storage->saveGroup($group);
        return true;
    }

    public function deleteGroup(Group|string $group): Promise
    {
        $groupName = $group instanceof Group ? $group->getName() : strtolower($group);

        if (!isset($this->groups[$groupName])) {
            $resolver = new PromiseResolver();
            $resolver->resolve(false);
            return $resolver->getPromise();
        }

        return NovaPermsPlugin::getStorage()->deleteGroup($groupName);
    }

    public function registerGroup(Group $group): void
    {
        $this->groups[strtolower($group->getName())] = $group;
    }

    public function cleanupGroup(string $name): void
    {
        unset($this->groups[strtolower($name)]);
    }

    /** @return Group[] */
    public function getAllGroups(): array
    {
        return array_values($this->groups);
    }

    public function processGroupDeletion(string $groupName): void
    {
        $groupName = strtolower($groupName);

        $this->cleanupGroup($groupName);

        foreach ($this->getAllGroups() as $group) {
            $changed = false;
            foreach ($group->getInheritances() as $node) {
                if (strtolower($node->getGroup()) === $groupName) {
                    $group->removePermission($node);
                    $changed = true;
                }
            }
            if ($changed) {
                NovaPermsPlugin::getStorage()->saveGroup($group);
            }
        }
    }
}
