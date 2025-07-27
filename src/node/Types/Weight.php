<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\Context;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class Weight extends AbstractNode
{
    private int $weight;

    public function __construct(int $weight, bool $value, int $expiry, Context $context)
    {
        $key = "weight.$weight";
        parent::__construct($key, $value, $expiry, $context);
        $this->weight = $weight;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    public static function fromNodeString(string $key, bool $value, int $expiry, Context $context): ?self
    {
        if (!str_starts_with($key, 'weight.')) return null;
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2 || !is_numeric($parts[1])) return null;

        return new self((int)$parts[1], $value, $expiry, $context);
    }

    public static function builder($weight): AbstractNodeBuilder
    {
        return new Builder($weight);
    }
}

class Builder extends AbstractNodeBuilder
{
    private int $weight;

    public function __construct(int $weight)
    {
        $this->weight = $weight;
    }

    public function weight(int $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function build(): AbstractNode
    {
        return new Weight($this->weight, $this->getValue(), $this->getExpiry(), $this->getContext());
    }
}
