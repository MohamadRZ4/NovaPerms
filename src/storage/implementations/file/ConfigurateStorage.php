<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file;

use MohamadRZ\NovaPerms\configs\PrimaryKeys;
use MohamadRZ\NovaPerms\node\serializers\NodeDeserializer;
use MohamadRZ\NovaPerms\node\serializers\NodeSerializer;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\storage\implementations\file\loaders\ConfigurateLoader;
use MohamadRZ\NovaPerms\storage\implementations\StorageImplementation;
use MohamadRZ\NovaPerms\model\{User, Group, Track};
use pocketmine\utils\Config;

class ConfigurateStorage implements StorageImplementation
{
    protected NovaPermsPlugin $plugin;
    protected string $implementationName;
    protected ConfigurateLoader $loader;
    protected string $dataDirectory;
    protected string $dataDirectoryName;

    protected array $groupCache = [];

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

        if ($this->loader === null) {
            $this->plugin->getLogger()->error("Loader not initialized");
            return null;
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
            if ($config === null) {
                $this->plugin->getLogger()->error("Failed to load config from: {$filePath}");
                return null;
            }
            return $config->getAll();
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error reading file {$filePath}: " . $e->getMessage());
            return null;
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

        if ($this->loader === null) {
            $this->plugin->getLogger()->error("Loader not initialized");
            return;
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
            if ($config === null) {
                $this->plugin->getLogger()->error("Failed to load config for saving: {$filePath}");
                return;
            }
            $config->setAll($data);
            $config->save();
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error saving file {$filePath}: " . $e->getMessage());
        }
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

        if ($this->loader === null) {
            $this->plugin->getLogger()->error("Loader not initialized");
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
                    $this->loadGroup($name);
                }
            }
        }
    }

    public function loadUser(string $primaryKey, ?string $username = null): User
    {
        if (empty($primaryKey)) {
            throw new \InvalidArgumentException("Primary key cannot be empty");
        }

        $userManager = NovaPermsPlugin::getUserManager();
        $configManager = NovaPermsPlugin::getConfigManager();

        $data = $this->readFile(StorageLocation::USERS, $primaryKey);
        $user = $userManager->getOrMake($primaryKey);
        $primaryKeyType = $configManager->getPrimaryKey();

        if ($primaryKeyType === PrimaryKeys::USERNAME) {
            if ($data === null) {
                if ($username === null || empty($username)) {
                    throw new \InvalidArgumentException("Username is required when primary key type is USERNAME and no data exists");
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
            } catch (\Exception $e) {
                $this->plugin->getLogger()->error("Error importing nodes for user {$primaryKey}: " . $e->getMessage());
            }
        }

        return $user;
    }

    public function loadUsers(array $primaryKeys): array
    {
        if (empty($primaryKeys)) {
            return [];
        }

        $configManager = NovaPermsPlugin::getConfigManager();

        $primaryKeyType = $configManager->getPrimaryKey();
        $users = [];

        if ($primaryKeyType === PrimaryKeys::USERNAME) {
            foreach ($primaryKeys as $primaryKey => $username) {
                if (empty($primaryKey)) {
                    $this->plugin->getLogger()->warning("Empty primary key found in loadUsers");
                    continue;
                }

                try {
                    $users[] = $this->loadUser($primaryKey, $username);
                } catch (\Exception $e) {
                    $this->plugin->getLogger()->error("Error loading user {$primaryKey}: " . $e->getMessage());
                }
            }
        } else {
            foreach ($primaryKeys as $primaryKey => $username) {
                if (empty($primaryKey)) {
                    $this->plugin->getLogger()->warning("Empty primary key found in loadUsers");
                    continue;
                }

                try {
                    $users[] = $this->loadUser($primaryKey, $username);
                } catch (\Exception $e) {
                    $this->plugin->getLogger()->error("Error loading user {$primaryKey}: " . $e->getMessage());
                }
            }
        }

        return $users;
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
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error saving user {$primaryKey}: " . $e->getMessage());
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
        } catch (\Exception $e) {
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

    protected function readNodes(?array $rawData): array
    {
        if (!is_array($rawData)) {
            return [];
        }

        try {
            return NodeDeserializer::deserialize($rawData);
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error deserializing nodes: " . $e->getMessage());
            return [];
        }
    }

    protected function writeNodes(?array $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        try {
            return NodeSerializer::serialize($data);
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error serializing nodes: " . $e->getMessage());
            return [];
        }
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
        $this->groupCache = [];
    }








    public function loadAllTracks(): void
    {
        // Track functionality not implemented
    }
    public function createAndLoadGroup(string $name): Group
    {
 /*       $group = new Group($name);
        $this->saveGroup($group);
        $this->groupCache[$name] = $group;
        return $group;*/
    }

    public function loadGroup(string $name): ?Group
    {
/*        if (isset($this->groupCache[$name])) {
            return $this->groupCache[$name];
        }

        $data = $this->readFile(StorageLocation::GROUPS, $name);
        if ($data === null) {
            return null;
        }

        $group = new Group($name);

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $permissions = $this->readNodes($data['permissions']);
            foreach ($permissions as $permission => $value) {
                $group->setPermission($permission, $value);
            }
        }

        $this->groupCache[$name] = $group;
        return $group;*/
    }

    public function saveGroup(Group $group): void
    {
/*        $data = [
            'name' => $group->getName(),
            'permissions' => []
        ];

        $this->writeNodes($data);
        $this->saveFile(StorageLocation::GROUPS, $group->getName(), $data);
        $this->groupCache[$group->getName()] = $group;*/
    }

    public function deleteGroup(Group $group): void
    {
/*        $this->saveFile(StorageLocation::GROUPS, $group->getName(), null);
        unset($this->groupCache[$group->getName()]);*/
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
