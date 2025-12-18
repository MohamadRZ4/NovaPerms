<?php

namespace MohamadRZ\NovaPerms\context;

use MohamadRZ\NovaPerms\context\calculator\ContextCalculator;
use pocketmine\player\Player;

class ContextManager {

    /** @var ContextCalculator[] */
    private array $calculators = [];

    public function register(ContextCalculator $calculator): void {
        $this->calculators[] = $calculator;
    }

    public function getContext(Player $player): ContextSet {
        $context = new ContextSet();

        foreach ($this->calculators as $calculator) {
            $calculator->calculate($player, $context);
        }

        return $context;
    }

    public function getAllPossibleContexts(): ContextSet {
        $context = new ContextSet();

        foreach ($this->calculators as $calculator) {
            foreach ($calculator->possible() as $key => $values) {
                foreach ($values as $value) {
                    $context->add($key, $value);
                }
            }
        }

        return $context;
    }
}
