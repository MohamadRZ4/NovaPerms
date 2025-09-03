<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class WeightNode extends AbstractNode
{
    private int $weight;

    public function __construct(
        int $weight,
        bool $value = true,
        int $expiry = -1
    ) {
        parent::__construct("weight.{$weight}", $value, $expiry, $context);
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
        return new WeightNodeBuilder($this->weight)
            ->value($this->value)
            ->expiry($this->expiry);
    }
}

class WeightNodeBuilder extends AbstractNodeBuilder
{
    private int $weight;
    public function __construct(int $weight) { $this->weight = $weight; }
    public function weight(int $weight): self { $this->weight = $weight; return $this; }
    public function build(): WeightNode
    {
        return new WeightNode(
            $this->weight,
            $this->value,
            $this->expiry
        );
    }
}