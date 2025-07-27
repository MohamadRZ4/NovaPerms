<?php

namespace MohamadRZ\NovaPerms\model\cache;

use MohamadRZ\NovaPerms\model\cache\interfaces\CacheProviderInterface;
use MohamadRZ\NovaPerms\model\cache\interfaces\CacheRegistryInterface;

class CacheRegistry implements CacheRegistryInterface
{
    private array $instances = [];

    public function register(string $name, CacheProviderInterface $provider): void
    {
        $this->instances[$name] = new CacheInstance($name, $provider);
    }

    public function get(string $name): ?CacheInstance
    {
        return $this->instances[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->instances[$name]);
    }

    public function remove(string $name): bool
    {
        if (isset($this->instances[$name])) {
            unset($this->instances[$name]);
            return true;
        }
        return false;
    }

    public function clear(): void
    {
        $this->instances = [];
    }

    public function getAll(): array
    {
        return $this->instances;
    }
}