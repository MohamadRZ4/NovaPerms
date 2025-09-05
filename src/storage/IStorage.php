<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\User;

interface IStorage
{
    public function init(): void;
    public function getName(): string;
    public function loadUser(string $username): ?User;
    public function loadUsers(array $usernames): array;
    public function saveUser(User $user): void;

    public function loadGroup(string $groupName): ?Group;
    public function createAndLoadGroup(string $groupName, array $nodes = []): void;
    public function saveGroup(Group $group): void;
    public function loadAllGroup(): void;
    public function saveAllGroup(): void;

}