<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\graph\PermissionResolver;
use MohamadRZ\NovaPerms\graph\ResolverConfig;
use MohamadRZ\NovaPerms\model\primarygroup\PrimaryGroupFactory;
use MohamadRZ\NovaPerms\model\primarygroup\PrimaryGroupHolder;
use MohamadRZ\NovaPerms\node\Node;
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
    private PrimaryGroupHolder $primaryGroup;

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
        $this->primaryGroup = PrimaryGroupFactory::create($this);
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

    /**
     * @return PrimaryGroupHolder
     */
    public function getPrimaryGroup(): PrimaryGroupHolder
    {
        return $this->primaryGroup;
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
            $primaryGroup = $this->getPrimaryGroup()->getStoredValue();

            foreach ($groupManager->getAllGroups() as $group) {
                $groupName = $group->getName();

                foreach ($group->getOwnNodes() as $node) {
                    if ($node instanceof InheritanceNode) {
                        $groupInheritanceMap[$groupName][] = $node;
                    } else {
                        $groupPermissionsMap[$groupName][] = $node;
                    }
                }
            }

            $rootNodes = $this->getOwnNodes();

            $resolver = new PermissionResolver(
                $rootNodes,
                $groupPermissionsMap,
                $groupInheritanceMap,
                NovaPermsPlugin::getAllKnownPermissions(),
                $primaryGroup,
                ResolverConfig::permissionsOnly()
            );

            $resolver->resolve(function($permissions) use ($attachment) {
                $attachment->clearPermissions();
                foreach ($permissions as $perm => $value) {
                    $attachment->setPermission($perm, $value);
                }
            });
        });
    }

    public function setNode(Node $node, bool $value = true): void
    {
        $this->runWhenInitialized(function() use ($node, $value) {
            parent::setNode($node, $value);
            $this->updatePermissions();
        });
    }

    public function unsetNode(Node $node): bool
    {
        $isChanged = parent::unsetNode($node);
        if ($isChanged) {
            $this->runWhenInitialized(function() {
                $this->updatePermissions();
            });
        }
        return $isChanged;
    }
}