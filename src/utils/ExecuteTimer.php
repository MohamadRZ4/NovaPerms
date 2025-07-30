<?php

namespace MohamadRZ\NovaPerms\utils;

class ExecuteTimer
{
    private float $start = 0;
    private float $end = 0;

    public function __construct()
    {
        $this->start = microtime(true);
    }

    public function end(): string
    {
        $this->end = microtime(true);
        $duration = ($this->end - $this->start) * 1000;
        return number_format($duration, 3);
    }
}
