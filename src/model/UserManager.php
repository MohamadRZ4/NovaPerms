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
        if ($this->getUser($name)) {
            return $this->getUser($name);
        } else {
            $user = new User($name);
            $user->addPermission(InheritanceNode::builder(GroupManager::DEFAULT_GROUP)->build());
            return $user;
        }
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

    public function loadUser(string $playerName): User
    {
        $user = NovaPermsPlugin::getStorage()->loadUser($playerName);
        $this->users[strtolower($user->getName())] = $user;
        return $user;
    }

    public function saveUser(string|User $player): void
    {
        $name = $player instanceof User
            ? $player->getName()
            : $player;

        NovaPermsPlugin::getStorage()->saveUser($name);
    }

    public function cleanupUser(User $user): void
    {
        if (isset($this->users[strtolower($user->getName())])) {
            unset($this->users[strtolower($user->getName())]);
        }
    }

    public function getUser(string $name): ?User
    {
        return $this->users[strtolower($name)] ?? null;
    }

    public function removeUser(string $name): void
    {
        unset($this->users[strtolower($name)]);
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