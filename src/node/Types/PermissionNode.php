<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class PermissionNode extends AbstractNode
{
    public static function builder(string $key): PermissionNodeBuilder
    {
        return new PermissionNodeBuilder($key);
    }

    public function getType(): string { return 'permission'; }

    public function toBuilder(): PermissionNodeBuilder
    {
        return new PermissionNodeBuilder($this->key)
            ->value($this->value)
            ->expiry($this->expiry);
    }
}

class PermissionNodeBuilder extends AbstractNodeBuilder
{
    public function __construct(string $key) { $this->key = $key; }
    public function build(): PermissionNode
    {
        return new PermissionNode(
            $this->key,
            $this->value,
            $this->expiry
        );
    }
}