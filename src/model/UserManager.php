<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

class UserManager
{
    /** @var array<string,User> */
    private array $users = [];

    public function getOrMake($name): User
    {
        $name = strtolower($name);
        return $this->users[$name] ?? $this->users[$name] = new User($name);
    }

    public function getUser($name): ?User
    {
        return $this->users[strtolower($name)] ?? null;
    }

    public function inNonDefaultUser(User $user): bool
    {
        $nodes = $user->getOwnPermissionNodes();
        if (count($nodes) === 1) {
            foreach ($nodes as $node) {
                if ($node instanceof InheritanceNode) {
                    if ($node->getKey() === GroupManager::DEFAULT_GROUP) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function saveUser(string|User $user): void
    {
        $user = $user instanceof User
            ? $user
            : $this->getUser(strtolower($user));

        NovaPermsPlugin::getStorage()->saveUser($user);
    }

    public function cleanupUser(string|User $user): void
    {
        $user = $user instanceof User
            ? $user->getName()
            : strtolower($user);
        if (isset($this->users[strtolower($user)])) {
            unset($this->users[strtolower($user)]);
        }
    }

    public function getAllUsers(): array
    {
        return array_values($this->users);
    }

    public function clearUsers(): void
    {
        $this->users = [];
    }
}