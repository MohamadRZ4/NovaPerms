<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\model\cache\CachedLoader;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

class GroupManager
{
    private $cache;
    private array $defaultGroups = [];

    public function __construct()
    {
        $this->cache = CachedLoader::create("groups")
            ->memory()
            ->permanent()
            ->build();
    }

    /**
     * Get all groups
     */
    public function getAll(): array
    {
        return $this->cache->getAll();
    }

    /**
     * Get or create group
     */
    public function getOrMake(string $primaryKey): Group
    {
        $key = strtolower($primaryKey);
        if ($this->cache->exists($key)) {
            return $this->cache->get($key);
        } else {
            $group = new Group($key);
            $this->cache->set($key, $group);
            return $group;
        }
    }

    /**
     * Get group if loaded
     */
    public function getIfLoaded(string $primaryKey): ?Group
    {
        $key = strtolower($primaryKey);
        return $this->cache->exists($key) ? $this->cache->get($key) : null;
    }

    /**
     * Check if group is loaded
     */
    public function isLoaded(string $primaryKey): bool
    {
        $key = strtolower($primaryKey);
        return $this->cache->exists($key);
    }

    /**
     * Unload group
     */
    public function unload(string $primaryKey): void
    {
        $key = strtolower($primaryKey);
        if ($this->cache->exists($key)) {
            $group = $this->cache->get($key);
            NovaPermsPlugin::getStorage()->saveGroup($group);
            $this->cache->delete($key);
        }
    }

    /**
     * Create new group
     */
    public function createGroup(string $name): Group
    {
        $group = new Group($name);
        $this->cache->set(strtolower($name), $group);
        return $group;
    }

    /**
     * Delete group
     */
    public function deleteGroup(string $name): bool
    {
        $key = strtolower($name);
        if ($this->cache->exists($key)) {
            $this->cache->delete($key);
            NovaPermsPlugin::getStorage()->deleteGroup($name);
            return true;
        }
        return false;
    }

    /**
     * Get default groups
     */
    public function getDefaultGroups(): array
    {
        if (empty($this->defaultGroups)) {
            foreach ($this->getAll() as $group) {
                if ($group->isDefault()) {
                    $this->defaultGroups[] = $group;
                }
            }
        }
        return $this->defaultGroups;
    }

    /**
     * Set default group
     */
    public function setDefaultGroup(string $groupName): void
    {
        // Remove default from all groups
        foreach ($this->getAll() as $group) {
            $group->setDefault(false);
        }

        // Set new default
        $group = $this->getOrMake($groupName);
        $group->setDefault(true);
        $this->defaultGroups = [$group];
    }

    /**
     * Get groups by weight (sorted)
     */
    public function getGroupsByWeight(): array
    {
        $groups = $this->getAll();
        usort($groups, function(Group $a, Group $b) {
            return $b->getWeight() <=> $a->getWeight();
        });
        return $groups;
    }

    /**
     * Retain specific groups
     */
    public function retainAll(array $primaryKeys): void
    {
        $allKeys = array_keys($this->cache->getAll());
        $primaryKeys = array_map('strtolower', $primaryKeys);
        $toRemove = array_diff($allKeys, $primaryKeys);

        foreach ($toRemove as $key) {
            $this->unload($key);
        }
    }

    /**
     * Load all groups from storage
     */
    public function loadAll(): void
    {
        $groups = NovaPermsPlugin::getStorage()->loadAllGroups();
        foreach ($groups as $group) {
            $this->cache->set(strtolower($group->getName()), $group);
        }
    }

    /**
     * Save all groups to storage
     */
    public function saveAll(): void
    {
        foreach ($this->getAll() as $group) {
            NovaPermsPlugin::getStorage()->saveGroup($group);
        }
    }
}
