<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\model\User;

class SQLiteStorage implements IStorage
{

    /**
     * @return void
     */
    #[\Override] public function init(): void
    {
        // TODO: Implement init() method.
    }

    /**
     * @param string $username
     * @return void
     */
    #[\Override] public function loadUser(string $username): void
    {
        // TODO: Implement loadUser() method.
    }

    /**
     * @param array $usernames
     * @return void
     */
    #[\Override] public function loadUsers(array $usernames): void
    {
        // TODO: Implement loadUsers() method.
    }

    /**
     * @param User $username
     * @return void
     */
    #[\Override] public function saveUser(User $username): void
    {
        // TODO: Implement saveUser() method.
    }

    /**
     * @param array $usernames
     * @return void
     */
    #[\Override] public function saveUsers(array $usernames): void
    {
        // TODO: Implement saveUsers() method.
    }

    /**
     * @param string $group
     * @return void
     */
    #[\Override] public function loadGroup(string $group): void
    {
        // TODO: Implement loadGroup() method.
    }

    /**
     * @param User $user
     * @return void
     */
    #[\Override] public function saveGroup(User $user): void
    {
        // TODO: Implement saveGroup() method.
    }

    /**
     * @return void
     */
    #[\Override] public function loadAllGroup(): void
    {
        // TODO: Implement loadAllGroup() method.
    }

    /**
     * @return void
     */
    #[\Override] public function saveAllGroup(): void
    {
        // TODO: Implement saveAllGroup() method.
    }
}