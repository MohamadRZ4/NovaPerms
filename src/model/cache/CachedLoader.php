<?php

namespace MohamadRZ\NovaPerms\model\cache;

use pocketmine\Server;

class CachedLoader
{
    /** @var array<string, CacheEntry> */
    private array $cache = [];
    private bool $useExpiry = true;
    private int $maxCacheSize = 1000;
    private bool $autoClearOffline = true;
    private int $cacheTimeout = 3600;

    public function __construct(array $config = [])
    {
        $this->useExpiry = $config['use_expiry'] ?? true;
        $this->maxCacheSize = $config['max_cache_size'] ?? 1000;
        $this->autoClearOffline = $config['auto_clear_offline'] ?? true;
        $this->cacheTimeout = $config['cache_timeout'] ?? 3600;
    }

    public function load(string $key, callable $dataLoader, ?int $expiryTime = null): mixed
    {
        $key = strtolower($key);

        if (isset($this->cache[$key])) {
            $entry = $this->cache[$key];
            if (!$this->useExpiry || !$entry->isExpired()) {
                return $entry->getData();
            }
            unset($this->cache[$key]);
        }

        $data = $dataLoader($key);
        $this->store($key, $data, $this->useExpiry ? $expiryTime : null);

        return $data;
    }

    public function store(string $key, mixed $data, ?int $expiryTime = null): void
    {
        $key = strtolower($key);

        $this->manageCacheSize();

        $this->cache[$key] = new CacheEntry($data, $this->useExpiry ? $expiryTime : null);
    }

    public function get(string $key): mixed
    {
        $key = strtolower($key);

        if (isset($this->cache[$key])) {
            $entry = $this->cache[$key];
            if (!$this->useExpiry || !$entry->isExpired()) {
                return $entry->getData();
            }
            unset($this->cache[$key]);
        }
        return null;
    }

    public function remove(string $key): void
    {
        $key = strtolower($key);
        unset($this->cache[$key]);
    }

    public function clearStaleCache(): void
    {
        $currentTime = time();
        foreach ($this->cache as $key => $entry) {
            if ($currentTime - $entry->getLastAccess() > $this->cacheTimeout) {
                unset($this->cache[$key]);
            }
        }
    }

    private function manageCacheSize(): void
    {
        if (count($this->cache) >= $this->maxCacheSize) {
            $entries = $this->cache;
            uasort($entries, fn($a, $b) => $a->getLastAccess() <=> $b->getLastAccess());
            $keysToRemove = array_keys(array_slice($entries, 0, count($entries) - $this->maxCacheSize + 10));
            foreach ($keysToRemove as $key) {
                unset($this->cache[$key]);
            }
        }
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    public function getCacheSize(): int
    {
        return count($this->cache);
    }
}