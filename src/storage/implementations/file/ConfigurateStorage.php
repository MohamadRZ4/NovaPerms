<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file;

use Exception;
use InvalidArgumentException;
use MohamadRZ\NovaPerms\configs\PrimaryKeys;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;
use MohamadRZ\NovaPerms\model\{Group, Track, User};
use MohamadRZ\NovaPerms\node\serializers\NodeDeserializer;
use MohamadRZ\NovaPerms\node\serializers\NodeSerializer;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\storage\implementations\file\loaders\ConfigurateLoader;
use MohamadRZ\NovaPerms\storage\implementations\StorageImplementation;

class ConfigurateStorage implements StorageImplementation
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
    )
    {
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

    public function loadUsers(array $primaryKeys): array
    {
        if (empty($primaryKeys)) {
            return [];
        }

        $configManager = NovaPermsPlugin::getConfigManager();

        $primaryKeyType = $configManager->getPrimaryKey();
        $users = [];

        foreach ($primaryKeys as $primaryKey => $username) {
            if (empty($primaryKey)) {
                $this->plugin->getLogger()->warning("Empty primary key found in loadUsers");
                continue;
            }

            try {
                $users[] = $this->loadUser($primaryKey, $username);
            } catch (Exception $e) {
                $this->plugin->getLogger()->error("Error loading user {$primaryKey}: " . $e->getMessage());
            }
        }

        return $users;
    }

    public function loadUser(string $primaryKey, ?string $username = null): User
    {
        if (empty($primaryKey)) {
            throw new InvalidArgumentException("Primary key cannot be empty");
        }

        $userManager = NovaPermsPlugin::getUserManager();
        $configManager = NovaPermsPlugin::getConfigManager();

        $data = $this->readFile(StorageLocation::USERS, $primaryKey);
        $user = $userManager->getOrMake($primaryKey);
        $primaryKeyType = $configManager->getPrimaryKey();

        if ($primaryKeyType === PrimaryKeys::USERNAME) {
            if ($data === null) {
                if (empty($username)) {
                    throw new InvalidArgumentException("Username is required when primary key type is USERNAME and no data exists");
                }
                $user->setUsername($username);
                $this->saveUser($user);
                return $user;
            } else {
                if (empty($data["username"])) {
                    if (!empty($username)) {
                        $user->setUsername($username);
                    } else {
                        $this->plugin->getLogger()->warning("No username found in data for user: {$primaryKey}");
                    }
                } else {
                    $user->setUsername($data["username"]);
                }
            }
        } else {
            if ($data === null) {
                $this->plugin->getLogger()->warning("No data found for user with primary key: {$primaryKey}");
                return $user;
            }

            if (!empty($data["username"])) {
                $user->setUsername($data["username"]);
            } else if (!empty($username)) {
                $user->setUsername($username);
            }
        }

        if (isset($data["permissions"]) && is_array($data["permissions"])) {
            try {
                $nodes = $this->readNodes($data["permissions"]);
                $user->importNodes($nodes);
            } catch (Exception $e) {
                $this->plugin->getLogger()->error("Error importing nodes for user {$primaryKey}: " . $e->getMessage());
            }
        }

        return $user;
    }

    public function readFile(StorageLocation $location, string $name): ?array
    {
        if (empty($name)) {
            $this->plugin->getLogger()->error("File name cannot be empty");
            return null;
        }

        if (empty($this->dataDirectory)) {
            $this->plugin->getLogger()->error("Data directory not initialized");
            return null;
        }

        $directory = $this->dataDirectory . DIRECTORY_SEPARATOR . $location->value;

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                $this->plugin->getLogger()->error("Failed to create directory: {$directory}");
                return null;
            }
        }

        $extension = $this->loader->getExtension();
        if (empty($extension)) {
            $this->plugin->getLogger()->error("Loader extension is empty");
            return null;
        }

        $filePath = $directory . DIRECTORY_SEPARATOR . $name . $extension;

        if (!file_exists($filePath)) {
            return null;
        }

        if (!is_readable($filePath)) {
            $this->plugin->getLogger()->error("File is not readable: {$filePath}");
            return null;
        }

        try {
            $config = $this->loader->load($filePath);
            return $config->getAll();
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error reading file {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    public function saveUser(User $user): void
    {
        $primaryKey = $user->getPrimaryKey();
        if (empty($primaryKey)) {
            $this->plugin->getLogger()->error("User primary key is empty");
            return;
        }

        try {
            $data = [
                'username' => $user->getUsername() ?? null,
                'xuid' => $user->getXuid() ?? null,
                'permissions' => []
            ];

            $nodes = $user->getNodes();
            if ($nodes !== null) {
                $data['permissions'] = $this->writeNodes($nodes);
            }

            $this->saveFile(StorageLocation::USERS, $primaryKey, $data);
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error saving user {$primaryKey}: " . $e->getMessage());
        }
    }

    protected function writeNodes(?array $data): array
    {
        if (!is_array($data)) {
            return NodeSerializer::serialize([ImmutableContextSet::empty()]);
        }

        try {
            return NodeSerializer::serialize($data);
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error serializing nodes: " . $e->getMessage());
            return [];
        }
    }

    public function saveFile(StorageLocation $location, string $name, ?array $data): void
    {
        if (empty($name)) {
            $this->plugin->getLogger()->error("File name cannot be empty");
            return;
        }

        if (empty($this->dataDirectory)) {
            $this->plugin->getLogger()->error("Data directory not initialized");
            return;
        }

        $directory = $this->dataDirectory . DIRECTORY_SEPARATOR . $location->value;

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                $this->plugin->getLogger()->error("Failed to create directory: {$directory}");
                return;
            }
        }

        $extension = $this->loader->getExtension();
        if (empty($extension)) {
            $this->plugin->getLogger()->error("Loader extension is empty");
            return;
        }

        $filePath = $directory . DIRECTORY_SEPARATOR . $name . $extension;

        if ($data === null) {
            if (file_exists($filePath)) {
                if (!unlink($filePath)) {
                    $this->plugin->getLogger()->error("Failed to delete file: {$filePath}");
                }
            }
            return;
        }

        if (!is_writable($directory)) {
            $this->plugin->getLogger()->error("Directory is not writable: {$directory}");
            return;
        }

        try {
            $config = $this->loader->load($filePath);
            $config->setAll($data);
            $config->save();
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error saving file {$filePath}: " . $e->getMessage());
        }
    }

    protected function readNodes(?array $rawData): array
    {
        if (!is_array($rawData)) {
            return [];
        }

        try {
            return NodeDeserializer::deserialize($rawData);
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error deserializing nodes: " . $e->getMessage());
            return [];
        }
    }

    public function savePlayerData(string $primaryKey, ?string $username): bool
    {
        if (empty($primaryKey)) {
            $this->plugin->getLogger()->error("Primary key cannot be empty");
            return false;
        }

        try {
            $user = $this->loadUser($primaryKey, $username);
            $this->saveUser($user);
            return true;
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error saving player data for {$primaryKey}: " . $e->getMessage());
            return false;
        }
    }

    public function deletePlayerData(string $primaryKey): void
    {
        if (empty($primaryKey)) {
            $this->plugin->getLogger()->error("Primary key cannot be empty");
            return;
        }

        $this->saveFile(StorageLocation::USERS, $primaryKey, null);
    }

    public function init(): void
    {
        $this->dataDirectory = $this->plugin->getDataFolder() . DIRECTORY_SEPARATOR . $this->dataDirectoryName;
        if (!is_dir($this->dataDirectory)) {
            mkdir($this->dataDirectory, 0755, true);
        }

        foreach (StorageLocation::cases() as $location) {
            $subDir = $this->dataDirectory . DIRECTORY_SEPARATOR . $location->value;
            if (!is_dir($subDir)) {
                mkdir($subDir, 0755, true);
            }
        }
    }

    public function shutdown(): void
    {
        // im so sad..
    }


    public function loadAllGroups(): void
    {
        if (empty($this->dataDirectory)) {
            $this->plugin->getLogger()->error("Data directory not initialized");
            return;
        }

        $groupsDir = $this->dataDirectory . DIRECTORY_SEPARATOR . StorageLocation::GROUPS->value;
        if (!is_dir($groupsDir)) {
            return;
        }

        if (!is_readable($groupsDir)) {
            $this->plugin->getLogger()->error("Groups directory is not readable: {$groupsDir}");
            return;
        }

        $files = scandir($groupsDir);
        if ($files === false) {
            $this->plugin->getLogger()->error("Failed to scan groups directory: {$groupsDir}");
            return;
        }

        $extension = $this->loader->getExtension();
        if (empty($extension)) {
            $this->plugin->getLogger()->error("Loader extension is empty");
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || empty($file)) {
                continue;
            }

            if (str_ends_with($file, $extension)) {
                $name = substr($file, 0, -strlen($extension));
                if (!empty($name)) {
                    try {
                        $this->loadGroup($name);
                    } catch (Exception $e) {
                        $this->plugin->getLogger()->error("Error loading group {$name}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    public function loadGroup(string $name): ?Group
    {
        if (empty($name)) {
            $this->plugin->getLogger()->error("Group name cannot be empty");
            return null;
        }

        try {
            $data = $this->readFile(StorageLocation::GROUPS, $name);
            if ($data === null) {
                return null;
            }

            $group = new Group($name);

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                try {
                    $permissions = $this->readNodes($data['permissions']);
                    $group->importNodes($this->writeNodes($permissions));
                } catch (Exception $e) {
                    $this->plugin->getLogger()->error("Error reading permissions for group {$name}: " . $e->getMessage());
                }
            }

            return $group;
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error loading group {$name}: " . $e->getMessage());
            return null;
        }
    }

    public function createAndLoadGroup(string $name): Group
    {
        if (empty($name)) {
            throw new InvalidArgumentException("Group name cannot be empty");
        }

        $existingData = $this->readFile(StorageLocation::GROUPS, $name);
        if ($existingData !== null) {
            $this->plugin->getLogger()->warning("Group {$name} already exists, loading existing group");
            return $this->loadGroup($name);
        }

        try {
            $group = new Group($name);
            $this->saveGroup($group);
            return $group;
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error creating group {$name}: " . $e->getMessage());
            throw $e;
        }
    }

    public function saveGroup(Group $group): void
    {
        $groupName = $group->getName();
        if (empty($groupName)) {
            $this->plugin->getLogger()->error("Group name is empty");
            return;
        }

        try {
            $data = [
                'name' => $groupName,
                'permissions' => []
            ];

            $nodes = $group->getNodes();
            if (is_array($nodes)) {
                $data['permissions'] = $this->writeNodes($nodes);
            }

            $this->saveFile(StorageLocation::GROUPS, $groupName, $data);

            $this->plugin->getLogger()->info("Group {$groupName} saved successfully");
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error saving group {$groupName}: " . $e->getMessage());
        }
    }

    public function deleteGroup(Group $group): void
    {

        $groupName = $group->getName();
        if (empty($groupName)) {
            $this->plugin->getLogger()->error("Group name is empty");
            return;
        }

        try {
            $existingData = $this->readFile(StorageLocation::GROUPS, $groupName);
            if ($existingData === null) {
                $this->plugin->getLogger()->warning("Group {$groupName} does not exist in storage");
                return;
            }

            $this->saveFile(StorageLocation::GROUPS, $groupName, null);

            $this->plugin->getLogger()->info("Group {$groupName} deleted successfully");
        } catch (Exception $e) {
            $this->plugin->getLogger()->error("Error deleting group {$groupName}: " . $e->getMessage());
        }
    }


    public function loadAllTracks(): void
    {
        // todo: load all track
    }


    public function createAndLoadTrack(string $name): Track
    {
        // Track system not implemented
        return new Track($name);
    }

    public function loadTrack(string $name): ?Track
    {
        // Track system not implemented
        return null;
    }

    public function saveTrack(Track $track): void
    {
        // Track system not implemented
    }

    public function deleteTrack(Track $track): void
    {
        // Track system not implemented
    }
}
