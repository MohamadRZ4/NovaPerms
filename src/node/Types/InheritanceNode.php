<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class InheritanceNode extends AbstractNode
{
    private string $group;

    public function __construct(
        string $group,
        bool $value = true,
        int $expiry = -1,
    ) {
        parent::__construct("group.{$group}", $value, $expiry);
        $this->group = $group;
    }
    public static function builder(string $group): InheritanceNodeBuilder
    {
        return new InheritanceNodeBuilder($group);
    }
    public function getType(): string { return 'inheritance'; }
    public function getGroup(): string { return $this->group; }
    public function toNodeString(): string
    {
        return "group.{$this->group}";
    }
    public function toBuilder(): InheritanceNodeBuilder
    {
        return (new InheritanceNodeBuilder($this->group))
            ->value($this->value)
            ->expiry($this->expiry);
    }
}

class InheritanceNodeBuilder extends AbstractNodeBuilder
{
    private string $group;
    public function __construct(string $group) { $this->group = $group; }
    public function group(string $group): self { $this->group = $group; return $this; }
    public function build(): InheritanceNode
    {
        return new InheritanceNode(
            $this->group,
            $this->value,
            $this->expiry
        );
    }
}