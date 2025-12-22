<?php

namespace MohamadRZ\NovaPerms\node;


use MohamadRZ\NovaPerms\context\ContextSet;

abstract class NodeBuilder
{
    protected string $key;
    protected bool $value = true;
    protected int $expiry = -1;
    protected ContextSet $contextSet;

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
    public function expiry(?int $expiry): self {
        if ($expiry !== null) {
            $this->expiry = $expiry;
        }
        return $this;
    }

    abstract public function build(): Node;
}