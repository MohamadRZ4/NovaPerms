<?php

namespace MohamadRZ\NovaPerms\model\cache;

use MohamadRZ\NovaPerms\model\cache\interfaces\CacheProviderInterface;

class CacheInstance
{
    public function __construct(
        private readonly string $name,
        private readonly CacheProviderInterface $provider
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getProvider(): CacheProviderInterface
    {
        return $this->provider;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->provider->get($key, $default);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->provider->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->provider->delete($key);
    }

    public function clear(): bool
    {
        return $this->provider->clear();
    }

    public function exists(string $key): bool
    {
        return $this->provider->exists($key);
    }

    public function getMultiple(array $keys, mixed $default = null): array
    {
        return $this->provider->getMultiple($keys, $default);
    }

    public function setMultiple(array $items, ?int $ttl = null): bool
    {
        return $this->provider->setMultiple($items, $ttl);
    }

    public function deleteMultiple(array $keys): bool
    {
        return $this->provider->deleteMultiple($keys);
    }

    public function increment(string $key, int $value = 1): int|false
    {
        return $this->provider->increment($key, $value);
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->provider->decrement($key, $value);
    }
}