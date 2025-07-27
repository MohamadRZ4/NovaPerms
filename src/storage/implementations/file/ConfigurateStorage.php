<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file;

use MohamadRZ\NovaPerms\configs\PrimaryKeys;
use MohamadRZ\NovaPerms\node\Types\DisplayName;
use MohamadRZ\NovaPerms\node\Types\Inheritance;
use MohamadRZ\NovaPerms\node\Types\Meta;
use MohamadRZ\NovaPerms\node\Types\Permission;
use MohamadRZ\NovaPerms\node\Types\Prefix;
use MohamadRZ\NovaPerms\node\Types\Suffix;
use MohamadRZ\NovaPerms\node\Types\Weight;
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
        $directory = $this->dataDirectory . DIRECTORY_SEPARATOR . $location->value;
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . DIRECTORY_SEPARATOR . $name . $this->loader->getExtension();

        if (!file_exists($filePath)) {
            return null;
        }

        try {
            $config = $this->loader->load($filePath);
            return $config->getAll();
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error reading file {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    public function saveFile(StorageLocation $location, string $name, ?array $data): void
    {
        $directory = $this->dataDirectory . DIRECTORY_SEPARATOR . $location->value;
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . DIRECTORY_SEPARATOR . $name . $this->loader->getExtension();

        if ($data === null) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return;
        }

        try {
            $config = $this->loader->load($filePath);
            $config->setAll($data);
            $config->save();
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error saving file {$filePath}: " . $e->getMessage());
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

    public function loadAllGroups(): void
    {
        $groupsDir = $this->dataDirectory . DIRECTORY_SEPARATOR . StorageLocation::GROUPS->value;
        if (!is_dir($groupsDir)) {
            return;
        }

        $files = scandir($groupsDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $extension = $this->loader->getExtension();
            if (str_ends_with($file, $extension)) {
                $name = substr($file, 0, -strlen($extension));
                $this->loadGroup($name);
            }
        }
    }

    public function loadAllTracks(): void
    {
        // Track functionality not implemented
    }

    public function loadUser(string $primaryKey, ?string $username = null): User
    {
        $data = $this->readFile(StorageLocation::USERS, $primaryKey);
        $user = NovaPermsPlugin::getUserManager()->getOrMake($primaryKey);
        $primaryKeyType = NovaPermsPlugin::getConfigManager()->getPrimaryKey();

        if ($primaryKeyType === PrimaryKeys::USERNAME && $data === null) {
            $user->setUsername($username);
            $this->saveUser($user);
            return $user;
        } else {
            $username = $data["username"];
            $user->setUsername($username);
        }

        $user->importNodes($this->readNodes($data["permissions"]));

        return $user;
    }

    public function loadUsers(array $primaryKeys): array
    {
        $primaryKeyType = NovaPermsPlugin::getConfigManager()->getPrimaryKey();
        $users = [];
        if ($primaryKeyType === PrimaryKeys::USERNAME) {
            foreach ($primaryKeys as $primaryKey => $username) {
                $users[] = $this->loadUser($username, $username);
            }
        } else {
            foreach ($primaryKeys as $primaryKey => $username) {
                $users[] = $this->loadUser($primaryKey, null);
            }
        }
        return $users;
    }

    public function saveUser(User $user): void
    {
        $data = [
            'username' => $user->getUsername() ?? null,
            'xuid' => $user->getXuid() ?? null,
            'permissions' => []
        ];

        $this->writeNodes($data, $user->getPermissions());
        $this->saveFile(StorageLocation::USERS, $user->getPrimaryKey(), $data);
    }

    public function createAndLoadGroup(string $name): Group
    {
        $group = new Group($name);
        $this->saveGroup($group);
        $this->groupCache[$name] = $group;
        return $group;
    }

    public function loadGroup(string $name): ?Group
    {
        if (isset($this->groupCache[$name])) {
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
        return $group;
    }

    public function saveGroup(Group $group): void
    {
        $data = [
            'name' => $group->getName(),
            'permissions' => []
        ];

        $this->writeNodes($data, $group->getPermissions());
        $this->saveFile(StorageLocation::GROUPS, $group->getName(), $data);
        $this->groupCache[$group->getName()] = $group;
    }

    public function deleteGroup(Group $group): void
    {
        $this->saveFile(StorageLocation::GROUPS, $group->getName(), null);
        unset($this->groupCache[$group->getName()]);
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

    public function savePlayerData(string $primaryKey, string $username): bool
    {
        $user = $this->loadUser($primaryKey, $username);
        $this->saveUser($user);
        return true;
    }

    public function deletePlayerData(string $primaryKey): void
    {
        $this->saveFile(StorageLocation::USERS, $primaryKey, null);
    }

    protected function readNodes(array $rawData): array
    {
        $result = [];

        foreach ($rawData as $permission) {
            $name = $permission["name"] ?? "";
            $value = $permission["value"] ?? true;
            $expire = $permission["expire"] ?? null;
            $context = $permission["context"] ?? [];

            $parts = explode(".", $name);
            if (count($parts) === 0) {
                continue;
            }

            $type = strtolower($parts[0]);
            $node = null;

            switch ($type) {
                case "perfix":
                    if (count($parts) >= 3) {
                        $node = Prefix::builder($parts[2], $parts[1]);
                    }
                    break;

                case "suffix":
                    if (count($parts) >= 3) {
                        $node = Suffix::builder($parts[2], $parts[1]);
                    }
                    break;

                case "displayname":
                    if (count($parts) >= 2) {
                        $node = DisplayName::builder($parts[1]);
                    }
                    break;

                case "group":
                    if (count($parts) >= 2) {
                        $node = Inheritance::builder($parts[1]);
                    }
                    break;

                case "wight":
                    if (count($parts) >= 2) {
                        $node = Weight::builder($parts[1]);
                    }
                    break;

                case "meta":
                    if (count($parts) >= 3) {
                        $node = Meta::builder($parts[1], $parts[2]);
                    }
                    break;
            }

            if (!$node) {
                $node = Permission::builder($name);
            }

            $node->value($value)->expiry($expire);

            $result[] = $node;
        }

        return $result;
    }

    protected function writeNodes(array &$data, array $nodes): void
    {
        foreach ($nodes as $permission => $value) {
            $data['permissions'][$permission] = (bool) $value;
        }
    }
}
