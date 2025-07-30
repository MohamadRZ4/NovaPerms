<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\Track;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\storage\implementations\StorageImplementation;
use MohamadRZ\NovaPerms\utils\AsyncInterface;
use MohamadRZ\NovaPerms\configs\PrimaryKeys;

class Storage extends AsyncInterface
{
    protected StorageImplementation $implementation;
    protected PrimaryKeys $primaryKey;

    public function __construct(NovaPermsPlugin $plugin)
    {
        parent::__construct($plugin);
        $this->primaryKey = $plugin->getConfigManager()->getPrimaryKey();

        $storageType = $plugin->getConfigManager()->getStorageMethod();
        $this->implementation = StorageFactory::createStorage($plugin, $storageType);
        $this->implementation->init();
    }

    public function getPlugin(): NovaPermsPlugin
    {
        return $this->plugin;
    }

    public function getImplementation(): StorageImplementation
    {
        return $this->implementation;
    }

    public function getPrimaryKey(): PrimaryKeys
    {
        return $this->primaryKey;
    }

    public function shutdown(): void
    {
        $this->implementation->shutdown();
    }

    public function getImplementationName(callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->getImplementationName(),
            $onSuccess,
            $onError
        );
    }

    public function loadUser(string $primaryKey, string $username, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->loadUser($primaryKey, $username),
            $onSuccess,
            $onError
        );
    }

    public function loadUsers(array $primaryKeys, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->loadUsers($primaryKeys),
            $onSuccess,
            $onError
        );
    }

    public function saveUser(User $user, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->saveUser($user),
            $onSuccess,
            $onError
        );
    }

    public function createAndLoadGroup(string $name, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->createAndLoadGroup($name),
            $onSuccess,
            $onError
        );
    }

    public function loadGroup(string $name, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->loadGroup($name),
            $onSuccess,
            $onError
        );
    }

    public function loadAllGroups(callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->loadAllGroups(),
            $onSuccess,
            $onError
        );
    }

    public function saveGroup(Group $group, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->saveGroup($group),
            $onSuccess,
            $onError
        );
    }

    public function deleteGroup(Group $group, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->deleteGroup($group),
            $onSuccess,
            $onError
        );
    }

    public function createAndLoadTrack(string $name, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->createAndLoadTrack($name),
            $onSuccess,
            $onError
        );
    }

    public function loadTrack(string $name, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->loadTrack($name),
            $onSuccess,
            $onError
        );
    }

    public function loadAllTracks(callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->loadAllTracks(),
            $onSuccess,
            $onError
        );
    }

    public function saveTrack(Track $track, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->saveTrack($track),
            $onSuccess,
            $onError
        );
    }

    public function deleteTrack(Track $track, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->deleteTrack($track),
            $onSuccess,
            $onError
        );
    }

    public function savePlayerData(string $primaryKey, string $username, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->savePlayerData($primaryKey, $username),
            $onSuccess,
            $onError
        );
    }

    public function deletePlayerData(string $primaryKey, callable $onSuccess, ?callable $onError = null): void
    {
        $this->async(
            fn() => $this->implementation->deletePlayerData($primaryKey),
            $onSuccess,
            $onError
        );
    }
}
