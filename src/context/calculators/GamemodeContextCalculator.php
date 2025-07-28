<?php

namespace MohamadRZ\NovaPerms\context\calculators;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\context\MutableContextSet;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class GamemodeContextCalculator implements ContextCalculator
{
    public function calculate(Player $player): ContextSet
    {
        $gamemode = match($player->getGamemode()) {
            GameMode::SURVIVAL => 'survival',
            GameMode::CREATIVE => 'creative',
            GameMode::ADVENTURE => 'adventure',
            GameMode::SPECTATOR => 'spectator',
            default => GameMode::SURVIVAL
        };

        return MutableContextSet::create()
            ->add($this->getContextKey(), $gamemode)
            ->immutableCopy();
    }

    public function getContextKey(): string
    {
        return 'gamemode';
    }
}