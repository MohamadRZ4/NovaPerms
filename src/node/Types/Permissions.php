<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class Permissions extends AbstractNode
{

    public function builder($key): AbstractNodeBuilder
    {
        return new Builder($key);
    }
}

class Builder extends AbstractNodeBuilder
{

    public function __construct()
    {
        
    }

    public function build(): AbstractNode
    {
        return new Permissions($this->getKey(), $this->getValue(), $this->getExpiry(), $this->getContext());
    }
}