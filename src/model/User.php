<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\Types\PermissionNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\Server;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;

class User extends PermissionHolder
{
    private string $username;
    protected ?Player $player = null;
    private ?PermissionAttachment $attachment = null;
    private bool $needsUpdate = true;

    public function __construct(string $username)
    {
        $this->username = $username;
    }

    public function getParent(): ?Player
    {
        if (isset($this->player) && $this->player->isOnline()) {
            return $this->player;
        } else {
            $parent = Server::getInstance()->getPlayerExact($this->getUsername());
            if ($parent !== null) {
                $this->player = $parent;
                return $this->player;
            }
        }

        return null;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
        $this->markForUpdate();
    }

    public function setNode(AbstractNode $node): void
    {
        parent::setNode($node);
        $this->markForUpdate();
    }

    public function unsetNode(AbstractNode $target): void
    {
        parent::unsetNode($target);
        $this->markForUpdate();
    }

    public function clearCache(): void
    {
        parent::clearCache();
        $this->markForUpdate();
    }

    private function markForUpdate(): void
    {
        $this->needsUpdate = true;
    }

    public function updatePermissions(): void
    {
        $player = $this->getParent();
        if ($player === null) {
            return;
        }

        if ($this->attachment === null) {
            $this->attachment = $player->addAttachment(NovaPermsPlugin::getInstance());
        }

        $this->attachment->clearPermissions();

        $context = $this->getPlayerContext($player);

        $allNodes = $this->getAllNodes(true, $context);

        foreach ($allNodes as $node) {
            if (!$node instanceof AbstractNode) return;
            $this->attachment->setPermission($node->getKey(), $node->getValue());
        }

        $this->needsUpdate = false;
    }

    public function autoUpdateIfNeeded(): void
    {
        if ($this->needsUpdate) {
            $this->updatePermissions();
        }
    }

    public function forceUpdate(): void
    {
        $this->needsUpdate = true;
        $this->updatePermissions();
    }

    private function getPlayerContext(Player $player): ContextSet
    {
        return NovaPermsPlugin::getContextManager()->calculateContexts($player);
    }

    public function removeAttachment(): void
    {
        if ($this->attachment !== null) {
            $this->attachment->clearPermissions();
            $this->attachment = null;
        }
    }

    public function onPlayerJoin(Player $player): void
    {
        $this->player = $player;
        $this->forceUpdate();
    }

    public function onPlayerQuit(): void
    {
        $this->removeAttachment();
        $this->player = null;
    }

    public function onWorldChange(): void
    {
        $this->forceUpdate();
    }

    public function getName(): ?string
    {
        return $this->username;
    }
}
