<?php

namespace MohamadRZ\NovaPerms\model\cache\interfaces;

use MohamadRZ\NovaPerms\model\cache\CacheInstance;

interface CacheRegistryInterface
{
    public function register(string $name, CacheProviderInterface $provider): void;
    public function get(string $name): ?CacheInstance;
    public function has(string $name): bool;
    public function remove(string $name): bool;
    public function clear(): void;
    public function getAll(): array;
}