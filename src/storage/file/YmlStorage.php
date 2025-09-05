<?php

namespace MohamadRZ\NovaPerms\storage\file;

use Exception;
use JsonException;
use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\serializers\NodeDeserializer;
use MohamadRZ\NovaPerms\node\serializers\NodeSerializer;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\storage\IStorage;
use Override;
use pocketmine\utils\Config;

class YmlStorage implements IStorage
{

    private Config $groups;
    private Config $users;
    /**
     * @return void
     */
    #[Override] public function init(): void
    {
        $dataPath = NovaPermsPlugin::getInstance()->getDataFolder();
        $this->groups = new Config($dataPath. "groups.yml", Config::YAML);
        $this->users = new Config($dataPath. "users.yml", Config::YAML);
    }

    public function getName(): string
    {
        return "YAML";
    }

    /**
     * @param string $username
     * @return ?User
     */
    #[Override] public function loadUser(string $username): ?User
    {
        return $this->loadTargetUser($username);
    }

    /**
     * @param array $usernames
     * @return array
     */
    #[Override] public function loadUsers(array $usernames): array
    {
        $result = [];
        foreach ($usernames as $username) {
            $result[$username] = $this->loadTargetUser($username);
        }
        return $result;
    }

    /**
     * @param User $user
     * @return void
     * @throws JsonException
     */
    #[Override] public function saveUser(User $user): void
    {
        if (NovaPermsPlugin::getUserManager()->inNonDefaultUser($user)) {
            $nodes = $user->getOwnPermissionNodes();
            $data = [
                "nodes" => $this->writeNodes($nodes)
            ];
            $this->users->set($user->getParent(), $data);
            $this->users->save();
        }
    }

    /**
     * @param string $groupName
     * @return ?Group
     */
    #[Override]
    public function loadGroup(string $groupName): ?Group {
        $groupData = $this->groups->get($groupName);

        if ($groupData) {
            $serializedNodes = $groupData["nodes"] ?? [];
            $group = NovaPermsPlugin::getGroupManager()->getOrMake($groupName);
            $nodes = $this->readNodes($serializedNodes);
            $result = [];
            foreach ($nodes as $node) {
                $result[$node->getKey()] = $node;
            }
            $group->setPermissions($result);
            return $group;
        }
        return null;
    }

    /**
     * @param Group $group
     * @return void
     * @throws JsonException
     */
    #[Override]
    public function saveGroup(Group $group): void
    {
        $nodes = $group->getOwnPermissionNodes();
        $data = [
            "nodes" => $this->writeNodes($nodes)
        ];
        $this->groups->set($group->getName(), $data);
        $this->groups->save();
    }

    /**
     * @return void
     */
    #[Override] public function loadAllGroup(): void
    {
        foreach ($this->groups->getAll() as $name => $serializedNodes) {
            $result = [];
            $group = NovaPermsPlugin::getGroupManager()->getOrMake($name);
            $nodes = $this->readNodes($serializedNodes);
            foreach ($nodes as $node) {
                $result[$node->getKey()] = $node;
            }
            $group->setPermissions($result);
            NovaPermsPlugin::getGroupManager()->registerGroup($group);
        }
    }

    /**
     * @return void
     * @throws JsonException
     */
    #[Override] public function saveAllGroup(): void
    {
        foreach (NovaPermsPlugin::getGroupManager()->getAllGroups() as $group) {
            $nodes = $group->getOwnPermissionNodes();
            $data = [
                "nodes" => $this->writeNodes($nodes)
            ];
            $this->groups->set($group->getName(), $data);
            $this->groups->save();
        }
    }

    /**
     * @throws JsonException
     */
    public function createAndLoadGroup(string $groupName, array $nodes = []): void
    {
        $group = NovaPermsPlugin::getGroupManager()->getOrMake($groupName);
        if (NodeSerializer::isSerializedNode($nodes)) {
            $nodes = $this->readNodes($nodes);
        }
        foreach ($nodes as $node) {
            $group->addPermission($node);
        }
        $this->saveGroup($group);
    }

    protected function writeNodes(?array $data): array
    {
        if (!is_array($data)) {
            return NodeSerializer::serialize([/*ImmutableContextSet::empty()*/]);
        }

        try {
            return NodeSerializer::serialize($data);
        } catch (Exception $e) {
            NovaPermsPlugin::getInstance()->getLogger()->error("Error serializing nodes: " . $e->getMessage());
            return [];
        }
    }

    protected function readNodes(?array $rawData): array
    {
        if (!is_array($rawData)) {
            return [];
        }

        try {
            return NodeDeserializer::deserialize($rawData);
        } catch (Exception $e) {
            NovaPermsPlugin::getInstance()->getLogger()->error("Error deserializing nodes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @param mixed $username
     * @return User
     */
    private function loadTargetUser(mixed $username): User
    {
        $user = NovaPermsPlugin::getUserManager()->getOrMake($username);
        $userData = $this->users->get($username);

        if ($userData) {
            $serializedNodes = $userData["nodes"] ?? [];
            $nodes = $this->readNodes($serializedNodes);
            $result = [];
            foreach ($nodes as $node) {
                $result[$node->getKey()] = $node;
            }
            $user->setPermissions($result);
            return $user;
        } else {
            $user->addGroup(GroupManager::DEFAULT_GROUP);
        }
        return $user;
    }
}