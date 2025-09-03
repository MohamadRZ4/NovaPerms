<?php

namespace MohamadRZ\NovaPerms\node;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\context\Context;
use MohamadRZ\NovaPerms\utils\Duration;

abstract class AbstractNodeBuilder
{
    protected string $key;
    protected bool $value = true;
    protected int $expiry = -1;

    public function key(string $key): self { $this->key = $key; return $this; }
    public function value(bool $value): self { $this->value = $value; return $this; }
    public function expiry(int $expiry): self { $this->expiry = $expiry; return $this; }

    abstract public function build(): AbstractNode;
}