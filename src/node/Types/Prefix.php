<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class Prefix extends AbstractNode
{

    public function builder($key): AbstractNodeBuilder
    {
        return new Builder($key);
    }
}

class Builder extends AbstractNodeBuilder
{

    public function __construct(string $prefix, int $weight)
    {

    }

    public function build(): AbstractNode
    {
        return new Prefix($this->getKey(), $this->getValue(), $this->getExpiry(), $this->getContext());
    }
}