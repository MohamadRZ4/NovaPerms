<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\bulkupdate\BulkUpdate;
use MohamadRZ\NovaPerms\bulkupdate\BulkUpdateStatistics;
use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\model\PermissionHolder;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\serialize\NodeDeserializer;
use MohamadRZ\NovaPerms\node\serialize\NodeSerializer;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
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

        $displayName = ["sqlite" => "SQLite", "mysql" => "MySQL"][$this->name] ?? ucfirst($this->name);
        $plugin->getLogger()->info("Loading storage provider... [{$displayName}]");

        $this->database = libasynql::create($plugin, $config, [
            "sqlite" => "schema/sqlite.sql",
            "mysql" => "schema/mysql.sql"
        ]);
 
        foreach (['users', 'groups', 'user_permissions', 'group_permissions'] as $table) {
            $this->database->executeGeneric("init.{$table}");
            $this->database->waitAll();
        }
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

        $sql = "SELECT u.primary_group, up.permission, up.value, up.expiry 
                FROM Users u 
                LEFT JOIN UserPermissions up ON u.username = up.username 
                WHERE u.username = :username";

        $this->database->executeImplRaw(
            [$sql],
            [['username' => $username]],
            [\poggit\libasynql\SqlThread::MODE_SELECT],
            function($results) use ($user, $resolver) {
                $rows = $results[0]->getRows();

                if (!empty($rows)) {
                    $user->getPrimaryGroup()->setStoredValue($rows[0]['primary_group'] ?? GroupManager::DEFAULT_GROUP);

                    $nodes = $this->rowsToNodes($rows);
                    $perm = [];
                    foreach ($nodes as $node) $perm[$node->getKey()] = $node;
                    $user->setPermissions($perm);

                    $primaryGroupName = $user->getPrimaryGroup()->getStoredValue();
                    if ($primaryGroupName && !isset($perm["group.{$primaryGroupName}"])) {
                        $inheritNode = InheritanceNode::builder($primaryGroupName)->build();
                        $user->addPermission($inheritNode);
                    }
                }

                $resolver->resolve($user);
            },
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    public function saveUser(User $user): Promise
    {
        $resolver = new PromiseResolver();
        $serialized = NodeSerializer::serialize($user->getOwnPermissionNodes());

        $queries = [
            "INSERT OR REPLACE INTO Users(username, primary_group) VALUES (:username, :primary_group)",
            "DELETE FROM UserPermissions WHERE username = :username"
        ];
        $args = [
            ['username' => $user->getName(), 'primary_group' => $user->getPrimaryGroup()->getStoredValue()],
            ['username' => $user->getName()]
        ];
        $modes = [\poggit\libasynql\SqlThread::MODE_GENERIC, \poggit\libasynql\SqlThread::MODE_GENERIC];

        foreach ($serialized as $node) {
            $queries[] = "INSERT INTO UserPermissions(username, permission, value, expiry) VALUES (:username, :permission, :value, :expiry)";
            $args[] = [
                'username' => $user->getName(),
                'permission' => $node['name'],
                'value' => $node['value'],
                'expiry' => $node['expire']
            ];
            $modes[] = \poggit\libasynql\SqlThread::MODE_INSERT;
        }

        $this->database->executeImplRaw(
            $queries,
            $args,
            $modes,
            fn() => $resolver->resolve(true),
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    public function loadUsers(array $usernames): Promise
    {
        $resolver = new PromiseResolver();
        if (empty($usernames)) {
            $resolver->resolve([]);
            return $resolver->getPromise();
        }

        $promises = [];
        foreach ($usernames as $username) {
            $promises[] = $this->loadUser($username);
        }

        Promise::all($promises)->onCompletion(
            fn($users) => $resolver->resolve($users),
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    public function loadGroup(string $groupName): Promise
    {
        $resolver = new PromiseResolver();

        $sql = "SELECT gp.permission, gp.value, gp.expiry 
                FROM Groups g 
                LEFT JOIN GroupPermissions gp ON g.name = gp.group_name 
                WHERE g.name = :name";

        $this->database->executeImplRaw(
            [$sql],
            [['name' => $groupName]],
            [\poggit\libasynql\SqlThread::MODE_SELECT],
            function($results) use ($groupName, $resolver) {
                $group = NovaPermsPlugin::getGroupManager()->getOrMake($groupName);
                $rows = $results[0]->getRows();

                if (!empty($rows)) {
                    $nodes = $this->rowsToNodes($rows);
                    $perm = [];
                    foreach ($nodes as $node) $perm[$node->getKey()] = $node;
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
        $serialized = NodeSerializer::serialize($group->getOwnPermissionNodes());

        $queries = [
            "INSERT OR IGNORE INTO Groups(name) VALUES (:name)",
            "DELETE FROM GroupPermissions WHERE group_name = :group_name"
        ];
        $args = [
            ['name' => $group->getName()],
            ['group_name' => $group->getName()]
        ];
        $modes = [\poggit\libasynql\SqlThread::MODE_GENERIC, \poggit\libasynql\SqlThread::MODE_GENERIC];

        foreach ($serialized as $node) {
            $queries[] = "INSERT INTO GroupPermissions(group_name, permission, value, expiry) VALUES (:group_name, :permission, :value, :expiry)";
            $args[] = [
                'group_name' => $group->getName(),
                'permission' => $node['name'],
                'value' => $node['value'],
                'expiry' => $node['expire']
            ];
            $modes[] = \poggit\libasynql\SqlThread::MODE_INSERT;
        }

        $this->database->executeImplRaw(
            $queries,
            $args,
            $modes,
            fn() => $resolver->resolve(true),
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    public function loadAllGroup(): Promise
    {
        $resolver = new PromiseResolver();

        $sql = "SELECT g.name, gp.permission, gp.value, gp.expiry 
                FROM Groups g 
                LEFT JOIN GroupPermissions gp ON g.name = gp.group_name";

        $this->database->executeImplRaw(
            [$sql],
            [[]],
            [\poggit\libasynql\SqlThread::MODE_SELECT],
            function($results) use ($resolver) {
                $rows = $results[0]->getRows();
                $groupsData = [];

                foreach ($rows as $row) {
                    $name = $row['name'];
                    if (!isset($groupsData[$name])) $groupsData[$name] = [];
                    if (isset($row['permission'])) $groupsData[$name][] = $row;
                }

                foreach ($groupsData as $name => $groupRows) {
                    $group = NovaPermsPlugin::getGroupManager()->getOrMake($name);
                    $nodes = $this->rowsToNodes($groupRows);
                    $perm = [];
                    foreach ($nodes as $node) $perm[$node->getKey()] = $node;
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

        $promises = [];
        foreach ($groups as $group) {
            $promises[] = $this->saveGroup($group);
        }

        Promise::all($promises)->onCompletion(
            fn() => $resolver->resolve(true),
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    public function deleteGroup(string $groupName): Promise
    {
        $resolver = new PromiseResolver();

        $this->database->executeImplRaw(
            ["DELETE FROM Groups WHERE name = :name"],
            [['name' => $groupName]],
            [\poggit\libasynql\SqlThread::MODE_GENERIC],
            function() use ($groupName, $resolver) {
                $group = NovaPermsPlugin::getGroupManager()->getIfLoaded($groupName);
                if ($group !== null) {
                    NovaPermsPlugin::getGroupManager()->processGroupDeletion($groupName);
                }
                $resolver->resolve(true);
            },
            fn() => $resolver->reject()
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

    public function applyBulkUpdate(BulkUpdate $operation): Promise
    {
        $resolver = new PromiseResolver();
        $startTime = microtime(true);
        $stats = new BulkUpdateStatistics();

        try {
            $queries = $operation->buildQueries();
            $sqlQueries = [];
            $argsArray = [];
            $modes = [];

            foreach ($queries as $query) {
                $sqlQueries[] = $query['sql'];
                $argsArray[] = $query['params'];
                $modes[] = \poggit\libasynql\SqlThread::MODE_GENERIC;
            }

            $this->database->executeImplRaw(
                $sqlQueries,
                $argsArray,
                $modes,
                function() use ($resolver, $operation, $stats, $startTime) {
                    $stats->executionTime = microtime(true) - $startTime;

                    if ($operation->isTrackStatistics()) {
                        $this->collectBulkStats($operation, $stats)->onCompletion(
                            function() use ($resolver, $stats, $operation) {
                                $this->triggerUpdates($operation);
                                $resolver->resolve($stats);
                            },
                            fn() => $resolver->reject()
                        );
                    } else {
                        $this->triggerUpdates($operation);
                        $resolver->resolve($stats);
                    }
                },
                function($error) use ($resolver) {
                    NovaPermsPlugin::getInstance()->getLogger()->error("Bulk update failed: " . ($error->getMessage() ?? 'Unknown'));
                    $resolver->reject();
                }
            );
        } catch (\Exception $e) {
            NovaPermsPlugin::getInstance()->getLogger()->error("Bulk update error: " . $e->getMessage());
            $resolver->reject();
        }

        return $resolver->getPromise();
    }

    protected function collectBulkStats(BulkUpdate $operation, BulkUpdateStatistics $stats): Promise
    {
        $resolver = new PromiseResolver();
        $queries = [];
        $args = [];

        if ($operation->shouldUpdateUsers()) {
            $queries[] = "SELECT COUNT(DISTINCT username) as count FROM UserPermissions";
            $args[] = [];
        }

        if ($operation->shouldUpdateGroups()) {
            $queries[] = "SELECT COUNT(DISTINCT group_name) as count FROM GroupPermissions";
            $args[] = [];
        }

        if (empty($queries)) {
            $resolver->resolve(true);
            return $resolver->getPromise();
        }

        $modes = array_fill(0, count($queries), \poggit\libasynql\SqlThread::MODE_SELECT);

        $this->database->executeImplRaw(
            $queries,
            $args,
            $modes,
            function($results) use ($stats, $operation, $resolver) {
                $idx = 0;
                if ($operation->shouldUpdateUsers()) {
                    $stats->affectedUsers = (int)($results[$idx++]->getRows()[0]['count'] ?? 0);
                }
                if ($operation->shouldUpdateGroups()) {
                    $stats->affectedGroups = (int)($results[$idx]->getRows()[0]['count'] ?? 0);
                }
                $resolver->resolve(true);
            },
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    protected function triggerUpdates(BulkUpdate $operation): void
    {
        if ($operation->shouldUpdateUsers()) {
            foreach (NovaPermsPlugin::getUserManager()->getAllUsers() as $user) {
                $user->updatePermissions();
            }
        }

        if ($operation->shouldUpdateGroups()) {
            foreach (NovaPermsPlugin::getGroupManager()->getAllGroups() as $group) {
                PermissionHolder::updateUsersForGroup($group->getName());
            }
        }
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
            NovaPermsPlugin::getInstance()->getLogger()->error("Deserialize error: " . $e->getMessage());
            return [];
        }
    }
}