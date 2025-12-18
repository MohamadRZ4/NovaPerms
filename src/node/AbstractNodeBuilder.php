<?php

namespace MohamadRZ\NovaPerms\node;


use MohamadRZ\NovaPerms\context\ContextSet;

abstract class AbstractNodeBuilder
{
    protected string $key;
    protected bool $value = true;
    protected int $expiry = -1;
    protected ContextSet $contextSet;
    protected bool $negated = false;

    public function __construct()
    {
        $this->contextSet = new ContextSet();
    }

    public function key(string $key): self { $this->key = $key; return $this; }
    public function value(bool $value): self { $this->value = $value; return $this; }

    public function contextSet(ContextSet $contextSet): self
    {
        //coming soon..
        /*$this->contextSet = $contextSet;*/
        return $this;
    }
    public function negated(bool $negated = true): self {
        $this->negated = $negated;
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