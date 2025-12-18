<?php

namespace MohamadRZ\NovaPerms\context;

use MohamadRZ\NovaPerms\context\calculator\ContextCalculator;
use pocketmine\player\Player;

class ContextManager {

    /** @var ContextCalculator[] */
    private array $calculators = [];

    public function registerCalculator(ContextCalculator $calculator): void {
        $this->calculators[] = $calculator;
    }

    public function clearCalculators(): void {
        $this->calculators = [];
    }

    public function getContext(Player $player): ImmutableContextSet {
        $collector = new PermissionContextCollector();

        foreach ($this->calculators as $calculator) {
            $calculator->calculate($player, $collector);
        }

        return $collector->toImmutableSet();
    }

    public function getAllPossibleContexts(): ImmutableContextSet {
        $builder = [];

        foreach ($this->calculators as $calculator) {
            $potential = $calculator->estimatePotentialContexts();
            foreach ($potential->getAll() as $key => $values) {
                foreach ($values as $value) {
                    $builder[$key][] = $value;
                }
            }
        }

        return new ImmutableContextSet($builder);
    }
}
