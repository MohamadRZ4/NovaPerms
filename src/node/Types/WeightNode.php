<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class WeightNode extends AbstractNode
{
    private string $weight;

    public function __construct(
        string $weight,
        bool $value = true,
        int $expiry = -1,
        ?BaseContextSet $context = null
    ) {
        parent::__construct("weight.{$weight}", $value, $expiry, $context);
        $this->weight = $weight;
    }
    public static function builder(string $group): WeightNodeBuilder
    {
        return new WeightNodeBuilder($group);
    }
    public function getType(): string { return 'weight'; }
    public function getWeight(): string { return $this->weight; }
    public function toNodeString(): string
    {
        return "weight.{$this->weight}";
    }
    public function toBuilder(): WeightNodeBuilder
    {
        return new WeightNodeBuilder($this->weight)
            ->value($this->value)
            ->expiry($this->expiry)
            ->withContext($this->context);
    }
}

class WeightNodeBuilder extends AbstractNodeBuilder
{
    private string $weight;
    public function __construct(string $weight) { $this->weight = $weight; }
    public function weight(string $weight): self { $this->weight = $weight; return $this; }
    public function build(): WeightNode
    {
        return new WeightNode(
            $this->weight,
            $this->value,
            $this->expiry,
            $this->context
        );
    }
}