<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file;

use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\storage\implementations\file\loaders\ConfigurateLoader;
use MohamadRZ\NovaPerms\storage\implementations\StorageImplementation;
use MohamadRZ\NovaPerms\model\{User, Group, Track};

abstract class AbstractConfigurateStorage implements StorageImplementation
{
    protected NovaPermsPlugin $plugin;
    protected string $implementationName;
    protected ConfigurateLoader $loader;
    protected string $dataDirectory;
    protected string $dataDirectoryName;

    public function __construct(
        NovaPermsPlugin   $plugin,
        string            $implementationName,
        ConfigurateLoader $loader,
        string            $dataDirectoryName
    ) {
        $this->plugin = $plugin;
        $this->implementationName = $implementationName;
        $this->loader = $loader;
        $this->dataDirectoryName = $dataDirectoryName;
    }

    public function getPlugin(): NovaPermsPlugin
    {
        return $this->plugin;
    }

    public function getImplementationName(): string
    {
        return $this->implementationName;
    }

    abstract protected function readFile(StorageLocation $location, string $name): ?array;

    abstract protected function saveFile(StorageLocation $location, string $name, ?array $data): void;

    public function init(): void
    {
        $this->dataDirectory = $this->plugin->getDataFolder() . DIRECTORY_SEPARATOR . $this->dataDirectoryName;
        if (!is_dir($this->dataDirectory)) {
            mkdir($this->dataDirectory, 0755, true);
        }
    }

    public function shutdown(): void
    {

    }

    public function loadUser(string $primaryKey, string $username): User
    {

    }

    public function loadUsers(array $primaryKeys): array
    {

    }

    public function saveUser(User $user): void
    {

    }

    public function createAndLoadGroup(string $name): Group
    {

    }

    public function loadGroup(string $name): ?Group
    {

    }

    public function saveGroup(Group $group): void
    {

    }

    public function deleteGroup(Group $group): void
    {

    }

    public function createAndLoadTrack(string $name): Track
    {

    }

    public function loadTrack(string $name): ?Track
    {

    }

    public function saveTrack(Track $track): void
    {

    }

    public function deleteTrack(Track $track): void
    {

    }

    public function savePlayerData(string $primaryKey, string $username): bool
    {

    }

    public function deletePlayerData(string $primaryKey): void
    {

    }

    public function getPlayerUuid(string $username): ?string
    {

    }

    public function getPlayerName(string $primaryKey): ?string
    {

    }

    // Abstract methods that must be implemented by subclasses
    abstract public function loadAllGroups(): void;
    abstract public function loadAllTracks(): void;

    protected function readNodes(array $data): array
    {

    }

    protected function writeNodes(array &$data, array $nodes): void
    {
        $permissions = [];
        $parents = [];
        $prefixes = [];
        $suffixes = [];
        $meta = [];


        if (!empty($permissions)) $data['permissions'] = $permissions;
        if (!empty($parents)) $data['parents'] = $parents;
        if (!empty($prefixes)) $data['prefixes'] = $prefixes;
        if (!empty($suffixes)) $data['suffixes'] = $suffixes;
        if (!empty($meta)) $data['meta'] = $meta;
    }
}
