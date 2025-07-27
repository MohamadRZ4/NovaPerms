<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\configs\PrimaryKeys;
use MohamadRZ\NovaPerms\storage\implementations\file\CombinedConfigurateStorage;
use MohamadRZ\NovaPerms\storage\implementations\file\loaders\YamlLoader;
use MohamadRZ\NovaPerms\storage\implementations\StorageImplementation;

class StorageFactory
{
    private static array $implementations = [];

    public static function registerImplementation(string $name, string $className): void
    {
        self::$implementations[strtolower($name)] = $className;
    }

    public static function createStorage(NovaPermsPlugin $plugin, string $type): StorageImplementation
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
        self::registerImplementation("yml", new CombinedConfigurateStorage(NovaPermsPlugin::getInstance(), "Yaml", new YamlLoader(), "datebase"));
    }
}
