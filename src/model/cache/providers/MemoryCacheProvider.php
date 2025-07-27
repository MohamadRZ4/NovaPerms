<?php

namespace MohamadRZ\NovaPerms\model\cache;

use MohamadRZ\NovaPerms\model\cache\providers\AbstractCacheProvider;

class MemoryCacheProvider extends AbstractCacheProvider
{
    private array $storage = [];
    private array $expiry = [];

    protected function initialize(): void
    {
        // Memory cache doesn't need initialization
    }

    protected function doGet(string $key): mixed
    {
        if ($this->isExpiryEnabled()) {
            $this->cleanExpired();
        }

        return $this->storage[$key] ?? null;
    }

    protected function doSet(string $key, mixed $value, ?int $ttl): bool
    {
        $this->storage[$key] = $value;

        if ($this->isExpiryEnabled() && $ttl !== null && $ttl > 0) {
            $this->expiry[$key] = time() + $ttl;
        }

        return true;
    }

    protected function doDelete(string $key): bool
    {
        unset($this->storage[$key]);

        if ($this->isExpiryEnabled()) {
            unset($this->expiry[$key]);
        }

        return true;
    }

    protected function doClear(): bool
    {
        $this->storage = [];

        if ($this->isExpiryEnabled()) {
            $this->expiry = [];
        }

        return true;
    }

    protected function doExists(string $key): bool
    {
        if ($this->isExpiryEnabled()) {
            $this->cleanExpired();
        }

        return isset($this->storage[$key]);
    }

    private function cleanExpired(): void
    {
        if (!$this->isExpiryEnabled()) {
            return;
        }

        $now = time();
        foreach ($this->expiry as $key => $expireTime) {
            if ($expireTime <= $now) {
                unset($this->storage[$key], $this->expiry[$key]);
            }
        }
    }

    public function getStorageInfo(): array
    {
        return [
            'total_items' => count($this->storage),
            'expired_items' => $this->isExpiryEnabled() ? count($this->expiry) : 0,
            'memory_usage' => memory_get_usage(),
            'expiry_enabled' => $this->isExpiryEnabled()
        ];
    }
}
