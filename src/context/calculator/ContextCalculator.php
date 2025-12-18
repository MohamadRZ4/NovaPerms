<?php

namespace MohamadRZ\NovaPerms\context\calculator;

use MohamadRZ\NovaPerms\context\ContextSet;

interface ContextCalculator {

    public function calculate(mixed $target, ContextSet $context): void;

    /** @return array<string, string[]> */
    public function possible(): array;
}
