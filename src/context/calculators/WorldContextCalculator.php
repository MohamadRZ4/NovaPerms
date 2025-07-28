<?php

namespace MohamadRZ\NovaPerms\context\calculators;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\context\MutableContextSet;
use pocketmine\player\Player;

class WorldContextCalculator implements ContextCalculator
{
    public function calculate(Player $player): ContextSet
    {
        return MutableContextSet::create()
            ->add($this->getContextKey(), $player->getWorld()->getFolderName())
            ->immutableCopy();
    }

    public function getContextKey(): string
    {
        return 'world';
    }
}
