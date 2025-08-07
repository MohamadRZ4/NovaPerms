<?php

namespace MohamadRZ\NovaPerms\context;

use MohamadRZ\NovaPerms\context\calculators\ContextCalculator;
use MohamadRZ\NovaPerms\context\calculators\GamemodeContextCalculator;
use MohamadRZ\NovaPerms\context\calculators\WorldContextCalculator;
use MohamadRZ\NovaPerms\context\providers\DefaultContextProvider;
use MohamadRZ\NovaPerms\context\providers\StaticContextProvider;
use pocketmine\player\Player;

class ContextManager
{
    private array $calculators = [];
    private StaticContextProvider $staticProvider;
    private DefaultContextProvider $defaultProvider;

    public function __construct(string $configPath)
    {
        $this->staticProvider = new StaticContextProvider($configPath);
        $this->defaultProvider = new DefaultContextProvider($configPath);

        $this->registerCalculator(new WorldContextCalculator());
        $this->registerCalculator(new GamemodeContextCalculator());
    }

    public function registerCalculator(ContextCalculator $calculator): void
    {
        $this->calculators[$calculator->getContextKey()] = $calculator;
    }

    public function unregisterCalculator(string $key): void
    {
        unset($this->calculators[$key]);
    }

    public function calculateContexts(Player $player): ContextSet
    {
        $contextSet = MutableContextSet::create();

        foreach ($this->calculators as $calculator) {
            $calculatedContexts = $calculator->calculate($player);
            $contextSet->addAll($calculatedContexts);
        }

        $contextSet->addAll($this->staticProvider->getStaticContexts());

        return $contextSet->immutableCopy();
    }

    public function getStaticProvider(): StaticContextProvider
    {
        return $this->staticProvider;
    }

    public function getDefaultProvider(): DefaultContextProvider
    {
        return $this->defaultProvider;
    }

    public function getCalculators(): array
    {
        return $this->calculators;
    }

    public function reload(): void
    {
        $this->staticProvider->reload();
        $this->defaultProvider->reload();
    }
}
