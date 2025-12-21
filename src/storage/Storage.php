<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\model\PermissionHolder;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\serialize\NodeDeserializer;
use MohamadRZ\NovaPerms\node\serialize\NodeSerializer;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;


class Storage implements IStorage
{
    protected DataConnector $database;
    protected string $name;

    public function __construct()
    {
        $plugin = NovaPermsPlugin::getInstance();
        $config = $plugin->getConfigManager()->getDatabase();

        $this->name = strtolower($config["type"] ?? "sqlite");
        $prettyNames = [
            "sqlite" => "SQLite",
            "mysql"  => "MySQL"
        ];

        $displayName = $prettyNames[strtolower($this->name)] ?? ucfirst($this->name);

        NovaPermsPlugin::getInstance()->getLogger()->info(
            "Loading storage provider... [".$displayName."]"
        );

        $this->database = libasynql::create($plugin, $config, [
            "sqlite" => "schema/sqlite.sql",
            "mysql"  => "schema/mysql.sql"
        ]);

        $this->database->executeGeneric("init.users");
        $this->database->waitAll();
        $this->database->executeGeneric("init.groups");
        $this->database->waitAll();
        $this->database->executeGeneric("init.user_permissions");
        $this->database->waitAll();
        $this->database->executeGeneric("init.group_permissions");
        $this->database->waitAll();
    }

    public function getDatabase(): DataConnector
    {
        return $this->database;
    }

    public function unload(): void
    {
        if (isset($this->database)) {
            $this->database->waitAll();
            $this->database->close();
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function loadUser(string $username): Promise
    {
        $resolver = new PromiseResolver();
        $user = NovaPermsPlugin::getUserManager()->getOrMake($username);

        $this->database->executeSelect(
            "data.users.get",
            ["username" => $username],
            function (array $rows) use ($username, $user, $resolver) {
                if (count($rows) > 0) {
                    if (isset($rows[0]['primary_group'])) {
                        $user->getPrimaryGroup()->setStoredValue($rows[0]['primary_group']);
                    } else {
                        $user->getPrimaryGroup()->setStoredValue(GroupManager::DEFAULT_GROUP);
                    }

                    $nodes = $this->rowsToNodes($rows);
                    $perm = [];
                    foreach ($nodes as $node) {
                        $perm[$node->getKey()] = $node;
                    }
                    $user->setPermissions($perm);
                }
                $resolver->resolve($user);
            },
            function () use ($resolver) {
                $resolver->reject();
            }
        );

        return $resolver->getPromise();
    }

    public function saveUser(User $user): Promise
    {
        $resolver = new PromiseResolver();

        $this->database->executeChange(
            "data.users.set",
            [
                "username" => $user->getName(),
                "primary_group" => $user->getPrimaryGroup()->getStoredValue() ?? GroupManager::DEFAULT_GROUP
            ],
            function() use ($user, $resolver) {
                $this->database->executeGeneric(
                    "data.user_permissions.deleteAll",
                    ["username" => $user->getName()],
                    function() use ($user, $resolver) {
                        $this->insertUserPermissionsSequentially($user, $resolver);
                    },
                    fn() => $resolver->reject()
                );
            },
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    protected function insertUserPermissionsSequentially(User $user, PromiseResolver $resolver): void
    {
        $nodes = $user->getOwnPermissionNodes();

        if (empty($nodes)) {
            $resolver->resolve(true);
            return;
        }

        $serializedNodes = NodeSerializer::serialize($nodes);
        $this->insertUserPermissionRecursive($user->getName(), $serializedNodes, 0, $resolver);
    }

    protected function insertUserPermissionRecursive(string $username, array $serializedNodes, int $index, PromiseResolver $resolver): void
    {
        if ($index >= count($serializedNodes)) {
            $resolver->resolve(true);
            return;
        }

        $nodeData = $serializedNodes[$index];

        $this->database->executeInsert(
            "data.user_permissions.add",
            [
                "username" => $username,
                "permission" => $nodeData['name'],
                "value" => $nodeData['value'],
                "expiry" => $nodeData['expire']
            ],
            function() use ($username, $serializedNodes, $index, $resolver) {
                $this->insertUserPermissionRecursive($username, $serializedNodes, $index + 1, $resolver);
            },
            fn() => $resolver->reject()
        );
    }

    public function loadUsers(array $usernames): Promise
    {
        $resolver = new PromiseResolver();

        if (empty($usernames)) {
            $resolver->resolve([]);
            return $resolver->getPromise();
        }

        $users = [];
        $this->loadUsersSequentially($usernames, 0, $users, $resolver);

        return $resolver->getPromise();
    }

    protected function loadUsersSequentially(array $usernames, int $index, array &$users, PromiseResolver $resolver): void
    {
        if ($index >= count($usernames)) {
            $resolver->resolve($users);
            return;
        }

        $this->loadUser($usernames[$index])->onCompletion(
            function($user) use ($usernames, $index, &$users, $resolver) {
                $users[] = $user;
                $this->loadUsersSequentially($usernames, $index + 1, $users, $resolver);
            },
            fn() => $resolver->reject()
        );
    }

    public function loadGroup(string $groupName): Promise
    {
        $resolver = new PromiseResolver();

        $this->database->executeSelect(
            "data.groups.get",
            ["name" => $groupName],
            function(array $rows) use ($groupName, $resolver) {
                $group = NovaPermsPlugin::getGroupManager()->getOrMake($groupName);

                if (count($rows) > 0) {
                    $nodes = $this->rowsToNodes($rows);
                    $perm = [];
                    foreach ($nodes as $node) {
                        $perm[$node->getKey()] = $node;
                    }
                    $group->setPermissions($perm);
                }

                $resolver->resolve($group);
            },
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    public function saveGroup(Group $group): Promise
    {
        $resolver = new PromiseResolver();

        $this->database->executeChange(
            "data.groups.add",
            ["name" => $group->getName()],
            function() use ($group, $resolver) {
                $this->database->executeGeneric(
                    "data.group_permissions.deleteAll",
                    ["group_name" => $group->getName()],
                    function() use ($group, $resolver) {
                        $this->insertGroupPermissionsSequentially($group, $resolver);
                    },
                    fn() => $resolver->reject()
                );
            },
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    protected function insertGroupPermissionsSequentially(Group $group, PromiseResolver $resolver): void
    {
        $nodes = $group->getOwnPermissionNodes();

        if (empty($nodes)) {
            $resolver->resolve(true);
            return;
        }

        $serializedNodes = NodeSerializer::serialize($nodes);
        $this->insertGroupPermissionRecursive($group->getName(), $serializedNodes, 0, $resolver);
    }

    protected function insertGroupPermissionRecursive(string $groupName, array $serializedNodes, int $index, PromiseResolver $resolver): void
    {
        if ($index >= count($serializedNodes)) {
            $resolver->resolve(true);
            return;
        }

        $nodeData = $serializedNodes[$index];

        $this->database->executeInsert(
            "data.group_permissions.add",
            [
                "group_name" => $groupName,
                "permission" => $nodeData['name'],
                "value" => $nodeData['value'],
                "expiry" => $nodeData['expire']
            ],
            function() use ($groupName, $serializedNodes, $index, $resolver) {
                $this->insertGroupPermissionRecursive($groupName, $serializedNodes, $index + 1, $resolver);
            },
            fn() => $resolver->reject()
        );
    }

    public function loadAllGroup(): Promise
    {
        $resolver = new PromiseResolver();

        $this->database->executeSelect(
            "data.groups.getAll",
            [],
            function(array $rows) use ($resolver) {
                $groupsData = [];

                foreach ($rows as $row) {
                    $groupName = $row['name'];
                    if (!isset($groupsData[$groupName])) {
                        $groupsData[$groupName] = [];
                    }
                    if (isset($row['permission'])) {
                        $groupsData[$groupName][] = $row;
                    }
                }

                foreach ($groupsData as $groupName => $groupRows) {
                    $group = NovaPermsPlugin::getGroupManager()->getOrMake($groupName);
                    $nodes = $this->rowsToNodes($groupRows);
                    $perm = [];
                    foreach ($nodes as $node) {
                        $perm[$node->getKey()] = $node;
                    }
                    $group->setPermissions($perm);
                    NovaPermsPlugin::getGroupManager()->registerGroup($group);
                }

                $resolver->resolve(true);
            },
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    public function saveAllGroup(): Promise
    {
        $resolver = new PromiseResolver();
        $groups = NovaPermsPlugin::getGroupManager()->getAllGroups();

        if (empty($groups)) {
            $resolver->resolve(true);
            return $resolver->getPromise();
        }

        $this->saveGroupsSequentially(array_values($groups), 0, $resolver);

        return $resolver->getPromise();
    }

    protected function saveGroupsSequentially(array $groups, int $index, PromiseResolver $resolver): void
    {
        if ($index >= count($groups)) {
            $resolver->resolve(true);
            return;
        }

        $this->saveGroup($groups[$index])->onCompletion(
            function() use ($groups, $index, $resolver) {
                $this->saveGroupsSequentially($groups, $index + 1, $resolver);
            },
            fn() => $resolver->reject()
        );
    }

    public function deleteGroup(string $groupName): Promise
    {
        $resolver = new PromiseResolver();

        $this->database->executeGeneric(
            "data.groups.delete",
            ["name" => $groupName],
            function() use ($groupName, $resolver) {
                $group = NovaPermsPlugin::getGroupManager()->getIfLoaded($groupName);
                if ($group !== null) {
                    NovaPermsPlugin::getGroupManager()->processGroupDeletion($groupName);
                }
                $resolver->resolve(true);
            },
            function(SqlError $error) use ($resolver) {
                $resolver->reject();
            }
        );

        return $resolver->getPromise();
    }

    public function createAndLoadGroup(string $groupName, array $nodes = []): Promise
    {
        $resolver = new PromiseResolver();
        $group = NovaPermsPlugin::getGroupManager()->getOrMake($groupName);
        $group->setPermissions($nodes);

        $this->saveGroup($group)->onCompletion(
            fn() => $resolver->resolve($group),
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    protected function rowsToNodes(array $rows): array
    {
        $rawData = [];

        foreach ($rows as $row) {
            if (!isset($row['permission'])) continue;

            $rawData[] = [
                "name" => $row['permission'],
                "value" => (bool)$row['value'],
                "expire" => $row['expiry'] !== null ? (int)$row['expiry'] : null
            ];
        }

        try {
            return NodeDeserializer::deserialize($rawData);
        } catch (\Exception $e) {
            NovaPermsPlugin::getInstance()->getLogger()->error(
                "Error deserializing nodes: " . $e->getMessage()
            );
            return [];
        }
    }
}