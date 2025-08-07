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
        $generatorName = $world->getProvider()->getWorldData()->getGenerator();

        $dimension = match(strtolower($generatorName)) {
            'nether', 'hell' => 'nether',
            'end', 'ender' => 'end',
            'normal', 'overworld', 'default' => 'overworld',
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
