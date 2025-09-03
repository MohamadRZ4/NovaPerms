<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\AbstractNode;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\permission\PermissionAttachment;

class User extends PermissionHolder
{
    private array $groups = [];

    /** @var PermissionAttachment|null */
    private ?PermissionAttachment $attachment = null;
    private array $lastAppliedPermissions = [];

    public function __construct()
    {

    }

    public function addGroup(string $groupName): void
    {
        $this->groups[] = $groupName;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function attachToPlayer(Player $player): void
    {
        if ($this->attachment === null) {
            $this->attachment = $player->addAttachment($player->getServer()->getPluginManager()->getPlugin("NovaPerms"));
        }
    }

    public function updatePermissions(Player $player, GroupManager $manager): void
    {
        if ($this->attachment === null) {
            $this->attachToPlayer($player);
        }

        $currentPermissions = $this->getAllInheritancePermissions($manager);
        $toAdd = array_diff_assoc($currentPermissions, $this->lastAppliedPermissions);
        $toRemove = array_diff_key($this->lastAppliedPermissions, $currentPermissions);

        foreach ($toRemove as $perm => $_) {
            $this->attachment->unsetPermission($perm);
        }

        foreach ($toAdd as $perm => $value) {
            $this->attachment->setPermission($perm, $value);
        }

        $this->lastAppliedPermissions = $currentPermissions;
    }
}
