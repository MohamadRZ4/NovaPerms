<?php

namespace MohamadRZ\NovaPerms\model\cache;

use MohamadRZ\NovaPerms\model\cache\interfaces\CacheProviderInterface;
use MohamadRZ\NovaPerms\model\cache\interfaces\CacheRegistryInterface;

class CachedLoader
{
    private static ?CacheRegistryInterface $registry = null;

    private static function getRegistry(): CacheRegistryInterface
    {
        if (self::$registry === null) {
            self::$registry = new CacheRegistry();
        }
        return self::$registry;
    }

    public static function create(string $name): CacheLoaderBuilder
    {
        return new CacheLoaderBuilder($name);
    }

    public static function register(string $name, CacheProviderInterface $provider): void
    {
        self::getRegistry()->register($name, $provider);
    }

    public static function get(string $name): ?CacheInstance
    {
        return self::getRegistry()->get($name);
    }

    public static function has(string $name): bool
    {
        return self::getRegistry()->has($name);
    }

    public static function remove(string $name): bool
    {
        return self::getRegistry()->remove($name);
    }

    public static function clear(): void
    {
        self::getRegistry()->clear();
    }

    public static function getAll(): array
    {
        return self::getRegistry()->getAll();
    }

    public static function setRegistry(CacheRegistryInterface $registry): void
    {
        self::$registry = $registry;
    }
}