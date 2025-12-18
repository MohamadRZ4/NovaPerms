<?php

namespace MohamadRZ\NovaPerms\context\calculator;

use MohamadRZ\NovaPerms\context\ContextSet;
use pocketmine\player\Player;
use pocketmine\player\GameMode;

class GameModeCalculator implements ContextCalculator {

    public function calculate(mixed $target, ContextSet $context): void {
        if (!$target instanceof Player) return;

        $context->add(
            'gamemode',
            strtolower($target->getGamemode()->name())
        );
    }

    public function possible(): array {
        $values = [];

        foreach (GameMode::getAll() as $gm) {
            $values[] = strtolower($gm->name());
        }

        return [
            'gamemode' => $values
        ];
    }
}
