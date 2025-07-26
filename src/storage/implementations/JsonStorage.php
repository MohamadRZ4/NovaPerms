<?php

namespace MohamadRZ\StellarRanks\storage\implementations;

use MohamadRZ\StellarRanks\model\object\Group;
use MohamadRZ\StellarRanks\model\object\Track;
use MohamadRZ\StellarRanks\model\object\User;
use MohamadRZ\StellarRanks\storage\StorageImplementation;
use MohamadRZ\StellarRanks\StellarRanks;
use MohamadRZ\StellarRanks\configs\PrimaryKeys;

class JsonStorage implements StorageImplementation
{

    public function getPlugin(): StellarRanks
    {
        // TODO: Implement getPlugin() method.
    }

    public function getImplementationName(): string
    {
        // TODO: Implement getImplementationName() method.
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }

    public function shutdown(): void
    {
        // TODO: Implement shutdown() method.
    }

    public function loadUser(string $primaryKey, string $username): User
    {
        // TODO: Implement loadUser() method.
    }

    public function loadUsers(array $primaryKeys): array
    {
        // TODO: Implement loadUsers() method.
    }

    public function saveUser(User $user): void
    {
        // TODO: Implement saveUser() method.
    }

    public function createAndLoadGroup(string $name): Group
    {
        // TODO: Implement createAndLoadGroup() method.
    }

    public function loadGroup(string $name): ?Group
    {
        // TODO: Implement loadGroup() method.
    }

    public function loadAllGroups(): void
    {
        // TODO: Implement loadAllGroups() method.
    }

    public function saveGroup(Group $group): void
    {
        // TODO: Implement saveGroup() method.
    }

    public function deleteGroup(Group $group): void
    {
        // TODO: Implement deleteGroup() method.
    }

    public function createAndLoadTrack(string $name): Track
    {
        // TODO: Implement createAndLoadTrack() method.
    }

    public function loadTrack(string $name): ?Track
    {
        // TODO: Implement loadTrack() method.
    }

    public function loadAllTracks(): void
    {
        // TODO: Implement loadAllTracks() method.
    }

    public function saveTrack(Track $track): void
    {
        // TODO: Implement saveTrack() method.
    }

    public function deleteTrack(Track $track): void
    {
        // TODO: Implement deleteTrack() method.
    }

    public function savePlayerData(string $primaryKey, string $username): bool
    {
        // TODO: Implement savePlayerData() method.
    }

    public function deletePlayerData(string $primaryKey): void
    {
        // TODO: Implement deletePlayerData() method.
    }
}
