<?php

namespace MohamadRZ\NovaPerms\node;

abstract class AbstractNode implements NodeInterface
{
    protected string $key;
    protected bool $value;
    protected int $expiry;

    public function __construct(
        string $key,
        bool $value = true,
        int $expiry = -1,
    )
    {
        $this->key = $key;
        $this->value = $value;
        $this->expiry = $expiry;
    }

    public function getKey(): string { return $this->key; }
    public function getValue(): bool { return $this->value; }
    public function getExpiry(): int { return $this->expiry; }

    public function toNodeString(): string { return $this->key; }
    abstract public function getType(): string;

    /** @return AbstractNodeBuilder */
    abstract public function toBuilder();
}
