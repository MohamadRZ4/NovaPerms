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

    private function getCacheKey(string|Player|User $primaryKeyOrUser): string {
        $primaryKeyType = NovaPermsPlugin::getConfigManager()->getPrimaryKey();

        if ($primaryKeyOrUser instanceof User) {
            return match ($primaryKeyType) {
                PrimaryKeys::XUID => $primaryKeyOrUser->getXuid(),
                PrimaryKeys::USERNAME => $primaryKeyOrUser->getUsername(),
            };
        }

        if ($primaryKeyOrUser instanceof Player) {
            return match ($primaryKeyType) {
                PrimaryKeys::XUID => $primaryKeyOrUser->getXuid(),
                PrimaryKeys::USERNAME => $primaryKeyOrUser->getName(),
            };
        }

        return $primaryKeyOrUser;
    }

    public function getOrMake($primaryKey): User
    {
        if ($this->cache->exists($primaryKey)) {
            return $this->cache->get($primaryKey);
        } else {
            $user = new User($primaryKey);
            $this->cache->set($primaryKey, $user);
            return $user;
        }
    }

    public function getIfLoaded($primaryKey): ?User
    {
        return $this->cache->exists($primaryKey)
            ? $this->cache->get($primaryKey)
            : null;
    }

    public function isLoaded($primaryKey): bool
    {
        return $this->cache->exists($primaryKey);
    }

    public function unload($primaryKey): void
    {
        $user = $this->getOrMake($primaryKey);
        NovaPermsPlugin::getStorage()->saveUser($user);
        $user->clearNodes();
        $this->cache->delete($primaryKey);
    }

    public function retainAll(array $primaryKeys): void
    {
        $allKeys = array_keys($this->cache->getAll());
        $toRemove = array_diff($allKeys, $primaryKeys);
        foreach ($toRemove as $key) {
            $this->unload($key);
        }
    }
}