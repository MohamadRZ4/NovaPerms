<?php

namespace MohamadRZ\NovaPerms\graph;

class ResolvedNode {
    public string $key;
    public mixed $value;
    public string $source;
    public int $priority;
    public int $depth;

    public function __construct(string $key, mixed $value, string $source, int $priority, int $depth = 0) {
        $this->key = $key;
        $this->value = $value;
        $this->source = $source;
        $this->priority = $priority;
        $this->depth = $depth;
    }
}