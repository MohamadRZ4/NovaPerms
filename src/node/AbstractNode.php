<?php

namespace MohamadRZ\NovaPerms\node;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;

abstract class AbstractNode implements NodeInterface
{
    protected string $key;
    protected bool $value;
    protected int $expiry;
    protected ?BaseContextSet $context;

    public function __construct(
        string $key,
        bool $value = true,
        int $expiry = -1,
        ?BaseContextSet $context = null
    )
    {
        $this->key = $key;
        $this->value = $value;
        $this->expiry = $expiry;
        $this->context = $context ?? ImmutableContextSet::empty();
    }

    public function getKey(): string { return $this->key; }
    public function getValue(): bool { return $this->value; }
    public function getExpiry(): int { return $this->expiry; }
    public function getContext(): ?BaseContextSet { return $this->context; }

    public function toNodeString(): string { return $this->key; }
    abstract public function getType(): string;

    /** @return AbstractNodeBuilder */
    abstract public function toBuilder();
}
