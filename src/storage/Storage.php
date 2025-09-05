<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\storage\file\YmlStorage;

class Storage
{
    private IStorage $storage;

    public function __construct()
    {
        $this->init();
    }

    /**
     * @return void
     */
    public function init(): void
    {
        $type = NovaPermsPlugin::getConfigManager()->getStorageType();
        switch ($type) {
            case StorageTypes::SQLite:
                $this->storage = new SQLiteStorage();
                break;
            case StorageTypes::MYSQL:
                break;
            default:
                $this->storage = new YmlStorage();
                break;
        }
        NovaPermsPlugin::getInstance()->getLogger()->info("Loading storage provider... [".$this->storage->getName()."]");
        $this->storage->init();
    }

    /**
     * @param string $username
     * @return User|null
     */
    public function loadUser(string $username): ?User
    {
        return $this->storage->loadUser($username);
    }

    /**
     * @param array $usernames
     * @return array
     */
    public function loadUsers(array $usernames): array
    {
        return $this->storage->loadUsers($usernames);
    }

    /**
     * @param User $user
     * @return void
     */
    public function saveUser(User $user): void
    {
        $this->storage->saveUser($user);
    }

    /**
     * @param string $groupName
     * @return Group|null
     */
    public function loadGroup(string $groupName): ?Group
    {
        return $this->storage->loadGroup($groupName);
    }

    /**
     * @param string $groupName
     * @param array $nodes
     * @return void
     */
    public function createAndLoadGroup(string $groupName, array $nodes = []): void
    {
        $this->storage->createAndLoadGroup($groupName, $nodes);
    }

    /**
     * @param Group $group
     * @return void
     */
    public function saveGroup(Group $group): void
    {
        $this->storage->saveGroup($group);
    }

    /**
     * @return void
     */
    public function loadAllGroup(): void
    {
        $this->storage->loadAllGroup();
    }

    /**
     * @return void
     */
    public function saveAllGroup(): void
    {
        $this->storage->saveAllGroup();
    }
}