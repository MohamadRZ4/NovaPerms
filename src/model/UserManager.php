<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\promise\Promise;
use pocketmine\Server;

class UserManager
{
    /** @var array<string,User> */
    private array $users = [];

    public function getOrMake($name): User
    {
        $name = strtolower($name);
        return $this->users[$name] ?? $this->users[$name] = new User($name);
    }

    public function loadUser(string $name): Promise
    {
        $name = strtolower($name);
        return NovaPermsPlugin::getStorage()->loadUser($name);
    }

    public function getUser($name): ?User
    {
        return $this->users[strtolower($name)] ?? null;
    }

    public function modifyUser(string $name, callable $consumer): void
    {
        $name = strtolower($name);

        if (isset($this->users[$name])) {
            $user = $this->users[$name];
            $consumer($user);
            $this->saveUser($user);
            return;
        }

        NovaPermsPlugin::getStorage()->loadUser($name)->onCompletion(
            function (User $user) use ($name, $consumer) {
                $this->users[$name] = $user;
                $user->setIsInitialized(true);

                $consumer($user);
                $this->saveUser($user);
            },
            function () use ($name) {
                Server::getInstance()->getLogger()->warning(
                    "Failed to load user {$name} for modifyUser"
                );
            }
        );
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

    public function saveUser(string|User $user): Promise
    {
        $user = $user instanceof User
            ? $user
            : $this->getUser(strtolower($user));

        return NovaPermsPlugin::getStorage()->saveUser($user);
    }

    public function cleanupUser(string|User $user): void
    {
        $user = $user instanceof User
            ? $user->getName()
            : strtolower($user);
        if (isset($this->users[$user])) {
            unset($this->users[$user]);
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