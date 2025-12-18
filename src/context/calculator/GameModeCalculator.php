<?php

namespace MohamadRZ\NovaPerms\context\calculator;

use MohamadRZ\NovaPerms\context\ContextConsumer;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;
use pocketmine\player\Player;
use pocketmine\player\GameMode;

class GameModeCalculator implements ContextCalculator {

    public function calculate($target, ContextConsumer $consumer): void {
        if (!$target instanceof Player) return;
        $consumer->accept("gamemode", strtolower($target->getGamemode()->name()));
    }

    public function estimatePotentialContexts(): ImmutableContextSet {
        $contexts = [];
        foreach (GameMode::getAll() as $gm) {
            $contexts["gamemode"] = strtolower($gm->name());
        }
        return new ImmutableContextSet($contexts);
    }
}
