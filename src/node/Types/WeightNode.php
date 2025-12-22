<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\node\Node;
use MohamadRZ\NovaPerms\node\NodeBuilder;

class WeightNode extends Node
{
    private int $weight;

    public function __construct(
        int $weight,
        ContextSet $contextSet,
        bool $value = true,
        int $expiry = -1
    ) {
        parent::__construct("weight.{$weight}", $contextSet, $value, $expiry);
        $this->weight = $weight;
    }
    public static function builder(int $weight): WeightNodeBuilder
    {
        return new WeightNodeBuilder($weight);
    }
    public function getType(): string { return 'weight'; }
    public function getWeight(): int { return $this->weight; }
    public function toNodeString(): string
    {
        return "weight.{$this->weight}";
    }
    public function toBuilder(): WeightNodeBuilder
    {
        return (new WeightNodeBuilder($this->weight))
            ->value($this->value)
            ->expiry($this->expiry)
            ->contextSet($this->contextSet);
    }
}

class WeightNodeBuilder extends NodeBuilder
{
    private int $weight;
    public function __construct(int $weight) { $this->weight = $weight; parent::__construct();}
    public function weight(int $weight): self { $this->weight = $weight; return $this; }
    public function build(): WeightNode
    {
        return new WeightNode(
            $this->weight,
            $this->contextSet,
            $this->value,
            $this->expiry
        );
    }
}