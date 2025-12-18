<?php

namespace MohamadRZ\NovaPerms\node;


use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;
use MohamadRZ\NovaPerms\context\MutableContextSet;

abstract class AbstractNodeBuilder
{
    protected string $key;
    protected bool $value = true;
    protected int $expiry = -1;
    protected ContextSet $contextSet;

    public function __construct()
    {
        $this->contextSet = new ImmutableContextSet();
    }

    public function key(string $key): self { $this->key = $key; return $this; }
    public function value(bool $value): self { $this->value = $value; return $this; }

    public function contextSet(ContextSet $contextSet): self
    {
        $this->contextSet = $contextSet;
        return $this;
    }
    public function expiry(?int $expiry): self {
        if ($expiry !== null) {
            $this->expiry = time() + $expiry;
        }
        return $this;
    }

    abstract public function build(): AbstractNode;
}