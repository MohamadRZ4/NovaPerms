<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\User;
use pocketmine\promise\Promise;

interface IStorage
{
    public function getName(): string;
    public function loadUser(string $username): Promise;
    public function loadUsers(array $usernames): Promise;
    public function saveUser(User $user): Promise;

    public function loadGroup(string $groupName): Promise;
    public function createAndLoadGroup(string $groupName, array $nodes = []): Promise;
    public function saveGroup(Group $group): Promise;
    public function loadAllGroup(): Promise;
    public function saveAllGroup(): Promise;

}