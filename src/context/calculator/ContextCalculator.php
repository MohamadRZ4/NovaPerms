<?php

namespace MohamadRZ\NovaPerms\context\calculator;

use MohamadRZ\NovaPerms\context\ContextConsumer;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;

interface ContextCalculator {

    /**
     * @param mixed $target
     * @param ContextConsumer $consumer
     */
    public function calculate($target, ContextConsumer $consumer): void;

    /**
     * @return ImmutableContextSet
     */
    public function estimatePotentialContexts(): ImmutableContextSet;
}
