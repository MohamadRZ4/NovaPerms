<?php

namespace MohamadRZ\NovaPerms\model;


use MohamadRZ\NovaPerms\model\cache\CachedLoader;
use MohamadRZ\NovaPerms\model\cache\CacheInstance;
use MohamadRZ\NovaPerms\node\AbstractNode;

class PermissionManager
{
    private CacheInstance $cache;
    private const CACHE_PREFIX = 'permissions:';
    private const USER_PREFIX = 'user:';
    private const GROUP_PREFIX = 'group:';

    public function __construct()
    {
        $this->cache = CachedLoader::create('permissions')
            ->memory()
            ->permanent()
            ->build();
    }

    public function addPermission(string $holderType, string $holderId, AbstractNode $permission): void
    {
        $key = $this->getKey($holderType, $holderId);
        $permissions = $this->getPermissions($holderType, $holderId);

        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->cache->set($key, $permissions);
        }
    }

    public function removePermission(string $holderType, string $holderId, AbstractNode $permission): void
    {
        $key = $this->getKey($holderType, $holderId);
        $permissions = $this->getPermissions($holderType, $holderId);

        $index = array_search($permission, $permissions);
        if ($index !== false) {
            unset($permissions[$index]);
            $permissions = array_values($permissions);
            $this->cache->set($key, $permissions);
        }
    }

    public function getPermissions(string $holderType, string $holderId): array
    {
        $key = $this->getKey($holderType, $holderId);
        return $this->cache->get($key, []);
    }

    public function setPermissions(string $holderType, string $holderId, array $permissions): void
    {
        $key = $this->getKey($holderType, $holderId);
        $this->cache->set($key, array_unique($permissions));
    }

    public function clearPermissions(string $holderType, string $holderId): void
    {
        $key = $this->getKey($holderType, $holderId);
        $this->cache->delete($key);
    }

    public function getPermissionCount(string $holderType, string $holderId): int
    {
        return count($this->getPermissions($holderType, $holderId));
    }

    private function getKey(string $holderType, string $holderId): string
    {
        $prefix = match($holderType) {
            'user' => self::USER_PREFIX,
            'group' => self::GROUP_PREFIX,
            default => $holderType . ':'
        };

        return self::CACHE_PREFIX . $prefix . $holderId;
    }

    public function getCacheStats(): array
    {
        return $this->cache->getProvider()->getStats();
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }
}
