<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\model\PermissionHolder;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\serializers\NodeDeserializer;
use MohamadRZ\NovaPerms\node\serializers\NodeSerializer;
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
        $this->database->executeGeneric("init.groups");
    }

    public function unload(): void
    {
        if (isset($this->database)) {
            $this->database->close();
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function loadUser(string $username): Promise {
        $resolver = new PromiseResolver();
        $user = NovaPermsPlugin::getUserManager()->getOrMake($username);

        $this->database->executeSelect(
            "data.users.get",
            ["username" => $username],
            function (array $rows) use ($user, $resolver) {
                $this->setNodes($rows, $user, $resolver);
                $user->updatePermissions();
            },
            fn() => $resolver->reject()
        );

        return $resolver->getPromise();
    }

    public function loadGroup(string $groupName): Promise
    {
        $resolver = new PromiseResolver();
        $this->database->executeSelect("data.groups.get", ["name" => $groupName], function(array $rows) use ($groupName, $resolver) {
            $group = NovaPermsPlugin::getGroupManager()->getOrMake($groupName);
            $this->setNodes($rows, $group, $resolver);
        }, fn() => $resolver->reject());
        return $resolver->getPromise();
    }

    public function saveUser(User $user): Promise
    {
        $resolver = new PromiseResolver();
        $serialized = json_encode($this->writeNodes($user->getOwnPermissionNodes()));
        $this->database->executeChange("data.users.set", [
            "username"    => $user->getName(),
            "permissions" => $serialized
        ], fn() => $resolver->resolve(true), fn() => $resolver->reject());
        return $resolver->getPromise();
    }

    public function saveGroup(Group $group): Promise
    {
        $resolver = new PromiseResolver();
        $serialized = json_encode($this->writeNodes($group->getOwnPermissionNodes()));
        $this->database->executeChange("data.groups.set", [
            "name"        => $group->getName(),
            "permissions" => $serialized
        ], fn() => $resolver->resolve(true), fn() => $resolver->reject());
        return $resolver->getPromise();
    }

    public function loadAllGroup(): Promise
    {
        $resolver = new PromiseResolver();
        $this->database->executeSelect("data.groups.getAll", [], function(array $rows) use ($resolver) {
            foreach ($rows as $row) {
                $group = NovaPermsPlugin::getGroupManager()->getOrMake($row['name']);
                $nodes = $this->readNodes(json_decode($row['permissions'], true));
                $perm = [];
                foreach ($nodes as $node) $perm[$node->getKey()] = $node;
                $group->setPermissions($perm);
                NovaPermsPlugin::getGroupManager()->registerGroup($group);
            }
            $resolver->resolve(true);
        }, fn() => $resolver->reject());
        return $resolver->getPromise();
    }

    public function saveAllGroup(): Promise
    {
        $resolver = new PromiseResolver();
        $promises = [];
        foreach (NovaPermsPlugin::getGroupManager()->getAllGroups() as $group) {
            $promises[] = $this->saveGroup($group);
        }
        Promise::all($promises)->onCompletion(fn() => $resolver->resolve(true), fn() => $resolver->reject());
        return $resolver->getPromise();
    }


    protected function writeNodes(?array $data): array
    {
        if (!is_array($data)) return NodeSerializer::serialize([]);
        try {
            return NodeSerializer::serialize($data);
        } catch (\Exception $e) {
            NovaPermsPlugin::getInstance()->getLogger()->error("Error serializing nodes: " . $e->getMessage());
            return [];
        }
    }

    protected function readNodes(?array $rawData): array
    {
        if (!is_array($rawData)) return [];
        try {
            return NodeDeserializer::deserialize($rawData);
        } catch (\Exception $e) {
            NovaPermsPlugin::getInstance()->getLogger()->error("Error deserializing nodes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @param array $usernames
     * @return Promise
     */
    #[\Override]
    public function loadUsers(array $usernames): Promise
    {
        $resolver = new PromiseResolver();
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

    /**
     * @param string $groupName
     * @param array $nodes
     * @return Promise
     */
    #[\Override]
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

    /**
     * @param array $rows
     * @param PermissionHolder $holder
     * @param PromiseResolver $resolver
     * @return void
     */
    private function setNodes(array $rows, PermissionHolder $holder, PromiseResolver $resolver): void
    {
        if (count($rows) > 0) {
            $nodes = $this->readNodes(json_decode($rows[0]['permissions'], true));
            $perm = [];
            foreach ($nodes as $node) $perm[$node->getKey()] = $node;
            $holder->setPermissions($perm);
        }
        $resolver->resolve($holder);
    }
}