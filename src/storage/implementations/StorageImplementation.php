<?php

namespace MohamadRZ\NovaPerms\storage\implementations;

use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\Track;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\configs\PrimaryKeys;

interface StorageImplementation
{
    /**
     * Get the plugin instance
     */
    public function getPlugin(): NovaPermsPlugin;

    /**
     * Get the implementation name
     */
    public function getImplementationName(): string;

    /**
     * Initialize the storage
     */
    public function init(): void;

    /**
     * Shutdown the storage
     */
    public function shutdown(): void;

    /**
     * Load a user by Primary Key and username
     */
    public function loadUser(string $primaryKey, string $username): User;

    /**
     * Load multiple users
     */
    public function loadUsers(array $primaryKeys): array;

    /**
     * Save a user
     */
    public function saveUser(User $user): void;

    /**
     * Create and load a group
     */
    public function createAndLoadGroup(string $name): Group;

    /**
     * Load a group
     */
    public function loadGroup(string $name): ?Group;

    /**
     * Load all groups
     */
    public function loadAllGroups(): void;

    /**
     * Save a group
     */
    public function saveGroup(Group $group): void;

    /**
     * Delete a group
     */
    public function deleteGroup(Group $group): void;

    /**
     * Create and load a track
     */
    public function createAndLoadTrack(string $name): Track;

    /**
     * Load a track
     */
    public function loadTrack(string $name): ?Track;

    /**
     * Load all tracks
     */
    public function loadAllTracks(): void;

    /**
     * Save a track
     */
    public function saveTrack(Track $track): void;

    /**
     * Delete a track
     */
    public function deleteTrack(Track $track): void;

    /**
     * Save player data
     */
    public function savePlayerData(string $primaryKey, string $username): bool;

    /**
     * Delete player data
     */
    public function deletePlayerData(string $primaryKey): void;
}
