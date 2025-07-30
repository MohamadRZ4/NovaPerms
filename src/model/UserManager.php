<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\model\cache\CachedLoader;

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

    public function getOrMake(string $primaryKey): User
    {
        if ($this->cache->exists($primaryKey)) {
            return $this->cache->get($primaryKey);
        } else {
            $user = new User($primaryKey);
            $this->cache->set($primaryKey, $user);
            return $user;
        }
    }
}