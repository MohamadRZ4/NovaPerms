<?php

namespace MohamadRZ\NovaPerms\context\calculators;

use MohamadRZ\NovaPerms\context\ContextSet;
use pocketmine\player\Player;

interface ContextCalculator
{
    public function calculate(Player $player): ContextSet;
    public function getContextKey(): string;
}
