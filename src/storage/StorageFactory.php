<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\storage\implementations\file\ConfigurateStorage;
use MohamadRZ\NovaPerms\storage\implementations\file\loaders\ConfigurateLoader;
use MohamadRZ\NovaPerms\storage\implementations\file\loaders\JsonLoader;
use MohamadRZ\NovaPerms\storage\implementations\file\loaders\YamlLoader;
use MohamadRZ\NovaPerms\storage\implementations\StorageImplementation;
use InvalidArgumentException;

final class StorageFactory
{
    private function __construct() {}

    public static function createStorage(NovaPermsPlugin $plugin, string $storageType): StorageImplementation
    {
        return match (strtolower($storageType)) {
            'yaml', 'yml' => self::createConfigurateStorage($plugin, 'YAML', new YamlLoader(), 'yaml-storage'),
            'json' => self::createConfigurateStorage($plugin, 'JSON', new JsonLoader(), 'json-storage'),
            'mysql', 'database', 'db' => self::createDatabaseStorage($plugin),
            'sqlite' => self::createSQLiteStorage($plugin),
            default => throw new InvalidArgumentException("Unknown storage type: $storageType")
        };
    }

    private static function createConfigurateStorage(
        NovaPermsPlugin $plugin,
        string $implementationName,
        ConfigurateLoader $loader,
        string $dataDirectoryName
    ): ConfigurateStorage {
        return new ConfigurateStorage($plugin, $implementationName, $loader, $dataDirectoryName);
    }

    private static function createDatabaseStorage(NovaPermsPlugin $plugin): StorageImplementation
    {

        throw new InvalidArgumentException("MySQL storage not implemented yet");
    }

    private static function createSQLiteStorage(NovaPermsPlugin $plugin): StorageImplementation
    {

        throw new InvalidArgumentException("SQLite storage not implemented yet");
    }

    public static function getAvailableStorageTypes(): array
    {
        return ['yaml', 'yml', 'json', 'mysql', 'sqlite'];
    }

    public static function isStorageTypeSupported(string $storageType): bool
    {
        return in_array(strtolower($storageType), self::getAvailableStorageTypes(), true);
    }
}
