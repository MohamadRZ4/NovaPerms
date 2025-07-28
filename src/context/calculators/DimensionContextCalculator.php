<?php

namespace MohamadRZ\NovaPerms\context\calculators;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\context\MutableContextSet;
use pocketmine\player\Player;

class DimensionContextCalculator implements ContextCalculator
{
    public function calculate(Player $player): ContextSet
    {
        $world = $player->getWorld();
        $dimension = match($world->getDimension()) {
            0 => 'overworld',
            1 => 'nether',
            2 => 'end',
            default => 'unknown'
        };

        return MutableContextSet::create()
            ->add($this->getContextKey(), $dimension)
            ->immutableCopy();
    }

    public function getContextKey(): string
    {
        return 'dimension';
    }
}
