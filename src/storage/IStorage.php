<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\User;

interface IStorage
{
    public function init(): void;
    public function loadUser(string $username): void;
    public function loadUsers(array $usernames): void;
    public function saveUser(User $user): void;
    public function saveUsers(array $usernames): void;

    public function loadGroup(string $groupName): void;
    public function saveGroup(Group $group): void;
    public function loadAllGroup(): void;
    public function saveAllGroup(): void;

}