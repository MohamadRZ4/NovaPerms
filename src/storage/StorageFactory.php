<?php

namespace MohamadRZ\StellarRanks\storage;

use MohamadRZ\StellarRanks\StellarRanks;
use MohamadRZ\StellarRanks\configs\PrimaryKeys;

class StorageFactory
{
    private static array $implementations = [];

    public static function registerImplementation(string $name, string $className): void
    {
        self::$implementations[strtolower($name)] = $className;
    }

    public static function createStorage(StellarRanks $plugin, string $type): StorageImplementation
    {
        $type = strtolower($type);

        if (!isset(self::$implementations[$type])) {
            throw new \InvalidArgumentException("Unknown storage type: $type");
        }

        $className = self::$implementations[$type];

        if (!class_exists($className)) {
            throw new \RuntimeException("Storage implementation class not found: $className");
        }

        if (!is_subclass_of($className, StorageImplementation::class)) {
            throw new \RuntimeException("Storage implementation must implement StorageImplementation interface");
        }

        return new $className($plugin);
    }

    public static function getAvailableImplementations(): array
    {
        return array_keys(self::$implementations);
    }

    public static function init(): void
    {
        // Register default implementations
        self::registerImplementation('yaml', YamlStorage::class);
        self::registerImplementation('json', JsonStorage::class);
        // Future implementations can be registered here
        // self::registerImplementation('mysql', MySqlStorage::class);
        // self::registerImplementation('sqlite', SqliteStorage::class);
        // self::registerImplementation('mongodb', MongoDbStorage::class);
    }
}
