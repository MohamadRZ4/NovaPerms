<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\configs\PrimaryKeys;
use MohamadRZ\NovaPerms\model\cache\CachedLoader;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\player\Player;

class UserManager
{

    private $cache;

    public function __construct()
    {
        $this->cache = CachedLoader::create("users")
            ->memory()
            ->permanent()
            ->build();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->cache->getAll();
    }

    public function getOrMake($username): User
    {
        if ($this->cache->exists($username)) {
            return $this->cache->get($username);
        } else {
            $user = new User($username);
            $this->cache->set($username, $user);
            return $user;
        }
    }

    public function getIfLoaded($username): ?User
    {
        return $this->cache->exists($username)
            ? $this->cache->get($username)
            : null;
    }

    public function isLoaded($username): bool
    {
        return $this->cache->exists($username);
    }

    public function unload($username): void
    {
        $user = $this->getOrMake($username);
        NovaPermsPlugin::getStorage()->saveUser($user);
        $user->clearNodes();
        $this->cache->delete($username);
    }

    public function retainAll(array $username): void
    {
        $allKeys = array_keys($this->cache->getAll());
        $toRemove = array_diff($allKeys, $username);
        foreach ($toRemove as $key) {
            $this->unload($key);
        }
    }
}