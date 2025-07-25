<?php

namespace MohamadRZ\StellarRanks\verbose;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PermissionCheckEvent extends PlayerEvent {
    private string $permission;
    private bool $cancelled = false;

    public function __construct(Player $player, string $permission) {
        $this->player = $player;
        $this->permission = $permission;
    }

    public function getPermission(): string {
        return $this->permission;
    }

    public function isCancelled(): bool {
        return $this->cancelled;
    }

    public function cancel(bool $cancelled): void {
        $this->cancelled = $cancelled;
    }
}