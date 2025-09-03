<?php

namespace MohamadRZ\NovaPerms\storage;

use MohamadRZ\NovaPerms\config\ConfigManager;
use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\serializers\NodeDeserializer;
use MohamadRZ\NovaPerms\node\serializers\NodeSerializer;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\utils\Config;

class YmlStorage implements IStorage
{

    private Config $groups;
    private Config $users;
    /**
     * @return void
     */
    #[\Override] public function init(): void
    {
        $dataPath = NovaPermsPlugin::getInstance()->getDataFolder();
        $this->groups = new Config($dataPath. "groups.yml", Config::YAML);
        $this->users = new Config($dataPath. "users.yml", Config::YAML);
    }

    /**
     * @param string $username
     * @return void
     */
    #[\Override] public function loadUser(string $username): void
    {
        // TODO: Implement loadUser() method.
    }

    /**
     * @param array $usernames
     * @return void
     */
    #[\Override] public function loadUsers(array $usernames): void
    {
        // TODO: Implement loadUsers() method.
    }

    /**
     * @param User $user
     * @return void
     */
    #[\Override] public function saveUser(User $user): void
    {
        $nodes = $user->getOwnPermissionNodes();
        $data = [
            "nodes" => $this->wirthNodes($nodes)
        ];
        $this->groups->set($user->getParent(), $data);
        $this->groups->save();
    }

    /**
     * @param array $usernames
     * @return void
     */
    #[\Override] public function saveUsers(array $usernames): void
    {
        // TODO: Implement saveUsers() method.
    }

    /**
     * @param string $groupName
     * @return void
     */
    #[\Override]
    public function loadGroup(string $groupName): void {
        $groupData = $this->groups->get($groupName);

        if ($groupData) {
            $serializedNodes = $groupData["nodes"] ?? [];
            $group = new Group($groupName);
            $nodes = $this->readNodes($serializedNodes);
            NovaPermsPlugin::getGroupManager()->registerGroup($group);
            foreach ($nodes as $node) {
                $group->addPermission($node);
            }
        }
    }

    /**
     * @param Group $group
     * @return void
     */
    #[\Override] public function saveGroup(Group $group): void
    {
        $nodes = $group->getOwnPermissionNodes();
        $data = [
            "nodes" => $this->wirthNodes($nodes)
        ];
        $this->groups->set($group->getName(), $data);
        $this->groups->save();
    }

    /**
     * @return void
     */
    #[\Override] public function loadAllGroup(): void
    {
        foreach ($this->groups->getAll() as $name => $serializedNodes) {
            $group = new Group($name);
            $nodes = $this->readNodes($serializedNodes);
            NovaPermsPlugin::getGroupManager()->registerGroup($group);
            foreach ($nodes as $node) {
                $group->setPermissions($node);
            }
        }
    }

    /**
     * @return void
     */
    #[\Override] public function saveAllGroup(): void
    {
        foreach (NovaPermsPlugin::getGroupManager()->getAllGroups() as $group) {
            $nodes = $group->getOwnPermissionNodes();
            $data = [
                "nodes" => $this->wirthNodes($nodes)
            ];
            $this->groups->set($group->getName(), $data);
            $this->groups->save();
        }
    }

    public function wirthNodes(array $nodes): array
    {
        return NodeSerializer::serialize($nodes);
    }

    public function readNodes(array $nodes): array
    {
        return NodeDeserializer::deserialize($nodes);
    }
}