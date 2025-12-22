<?php

namespace MohamadRZ\NovaPerms\node;


use MohamadRZ\NovaPerms\context\ContextSet;

abstract class Node implements NodeInterface
{
    protected string $key;
    protected ContextSet $contextSet;
    protected bool $value;
    protected int $expiry;

    public function __construct(
        string $key,
        ?ContextSet $contextSet = null,
        bool $value = true,
        int $expiry = -1
    ){
        $this->key = $key;
        $this->contextSet = $contextSet ?? new ContextSet();
        $this->value = $value;
        $this->expiry = $expiry;
    }

    public function getKey(): string { return $this->key; }
    public function getValue(): bool { return $this->value; }
    public function getExpiry(): int { return $this->expiry; }

    /**
     * @return ContextSet
     */
    public function getContext(): ContextSet
    {
        return $this->contextSet;
    }

    public function toNodeString(): string { return $this->key; }
    abstract public function getType(): string;

    /** @return NodeBuilder */
    abstract public function toBuilder();
}
