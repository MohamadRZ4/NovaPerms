<?php

namespace MohamadRZ\NovaPerms\model\cache\interfaces;

interface CacheProviderInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function getMultiple(array $keys, mixed $default = null): array;
    public function setMultiple(array $items, ?int $ttl = null): bool;
    public function deleteMultiple(array $keys): bool;
    public function exists(string $key): bool;
    public function increment(string $key, int $value = 1): int|false;
    public function decrement(string $key, int $value = 1): int|false;
    public function getAll(): array;
}