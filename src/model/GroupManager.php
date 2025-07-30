<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\model\cache\CachedLoader;
use MohamadRZ\NovaPerms\model\cache\CacheInstance;

class GroupManager
{
    private $cache;

    public function __construct()
    {
        $this->cache = CachedLoader::create("groups")
            ->memory()
            ->permanent()
            ->build();
    }

    /**
     * @return array<string, Group>
     */
    public function getAll(): array
    {
        return $this->cache->getAll();
    }

    /**
     * @param string $primaryKey
     * @return Group
     */
    public function getOrMake($primaryKey): Group
    {
        $key = (string)$primaryKey;
        if ($this->cache->exists($key)) {
            return $this->cache->get($key);
        } else {
            $group = new Group($key);
            $this->cache->set($key, $group);
            return $group;
        }
    }

    /**
     * @param string $primaryKey
     * @return Group|null
     */
    public function getIfLoaded($primaryKey): ?Group
    {
        $key = (string)$primaryKey;
        return $this->cache->exists($key) ? $this->cache->get($key) : null;
    }

    /**
     * @param string $primaryKey
     * @return bool
     */
    public function isLoaded($primaryKey): bool
    {
        $key = (string)$primaryKey;
        return $this->cache->exists($key);
    }

    /**
     * @param string $primaryKey
     * @return void
     */
    public function unload($primaryKey): void
    {
        $key = (string)$primaryKey;
        $this->cache->delete($key);
    }

    /**
     * @param array $primaryKeys
     * @return void
     */
    public function retainAll(array $primaryKeys): void
    {
        $allKeys = array_keys($this->cache->getAll());
        $primaryKeys = array_map('strval', $primaryKeys);
        $toRemove = array_diff($allKeys, $primaryKeys);
        foreach ($toRemove as $key) {
            $this->unload($key);
        }
    }
}
