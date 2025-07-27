<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class Permission extends AbstractNode
{

    public static function builder($key): AbstractNodeBuilder
    {
        return new Builder($key);
    }
}

class Builder extends AbstractNodeBuilder
{

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function build(): AbstractNode
    {
        return new Permission($this->getKey(), $this->getValue(), $this->getExpiry(), $this->getContext());
    }
}