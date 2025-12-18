<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\resolver\NodePermissionResolver;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\permission\PermissionManager;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;

class User extends PermissionHolder
{
    private string $name;
    private array $groups = [];
    private bool $isInitialized = false;
    /** @var \Closure[] */
    private array $initQueue = [];
    private ?Player $parent = null;

    /** @var PermissionAttachment|null */
    private ?PermissionAttachment $attachment = null;

    public function setIsInitialized(bool $isInitialized): void
    {
        $this->isInitialized = $isInitialized;

        if ($isInitialized) {
            foreach ($this->initQueue as $callback) {
                $callback();
            }
            $this->initQueue = [];
        }
    }

    private function runWhenInitialized(\Closure $callback): void
    {
        if ($this->isInitialized) {
            $callback();
        } else {
            $this->initQueue[] = $callback;
        }
    }

    public function __construct(string $playerName)
    {
        $this->name = $playerName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): ?Player
    {
        $name = $this->name;
        return $this->parent = Server::getInstance()->getPlayerExact($name);
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function addGroup(string $groupName): void
    {
        if (!in_array($groupName, $this->groups, true)) {
            $this->groups[] = $groupName;
        }
    }

    public function getAttachment(): ?PermissionAttachment
    {
        if ($this->attachment === null) {
            $player = $this->getParent();
            var_dump($player);
            if ($player !== null) {
                $this->attachment = $player->addAttachment(
                    NovaPermsPlugin::getInstance()
                );
                return $this->attachment;
            }
        }
        return $this->attachment;
    }


    public function updatePermissions(): void
    {
        $this->runWhenInitialized(function() {

            $attachment = $this->getAttachment();
            if ($attachment === null) return;

            $groupManager = NovaPermsPlugin::getGroupManager();
            $groupPermissionsMap = [];
            $groupInheritanceMap = [];

            foreach ($groupManager->getAllGroups() as $group) {
                $groupName = $group->getName();

                foreach ($group->getOwnPermissionNodes() as $node) {
                    if ($node instanceof InheritanceNode) {
                        $groupInheritanceMap[$groupName][] = $node;
                    } else {
                        $groupPermissionsMap[$groupName][] = $node;
                    }
                }
            }

            $rootNodes = $this->getOwnPermissionNodes();

            $resolver = new NodePermissionResolver(
                $rootNodes,
                $groupPermissionsMap,
                $groupInheritanceMap,
                NovaPermsPlugin::getAllKnownPermissions(),
                $this->getName()
            );

            $resolver->resolve();
        });
    }

    public function addPermission(AbstractNode|string|array $nodes, bool $value = true): void
    {
        $this->runWhenInitialized(function() use ($nodes, $value) {
            parent::addPermission($nodes, $value);
            $this->updatePermissions();
        });
    }

    public function auditTemporaryNodes(): bool
    {
        $changed = parent::auditTemporaryNodes();

        if ($changed) {
            $this->updatePermissions();
        }

        return $changed;
    }
}