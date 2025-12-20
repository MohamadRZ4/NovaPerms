<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class InheritanceNode extends AbstractNode
{
    private string $group;

    public function __construct(
        string $group,
        ?ContextSet $contextSet = null,
        bool $value = true,
        int $expiry = -1

    ) {
        parent::__construct("group.{$group}", $contextSet, $value, $expiry);
        $this->group = $group;
    }

    public static function builder(string $group): DisplayNameNodeBuilder
    {
        return new DisplayNameNodeBuilder($group);
    }

    public function getType(): string { return 'inheritance'; }
    public function getGroup(): string { return $this->group; }
    public function getContexts(): ContextSet { return $this->contextSet; }

    public function toNodeString(): string
    {
        return "group.{$this->group}";
    }

    public function toBuilder(): DisplayNameNodeBuilder
    {
        return (new DisplayNameNodeBuilder($this->group))
            ->value($this->value)
            ->expiry($this->expiry)
            ->contextSet($this->contextSet);
    }
}

class InheritanceNodeBuilder extends AbstractNodeBuilder
{
    private string $group;
    public function __construct(string $group) {
        $this->group = $group;
        parent::__construct();
    }
    public function group(string $group): self { $this->group = $group; return $this; }
    public function build(): InheritanceNode
    {
        return new InheritanceNode(
            $this->group,
            $this->contextSet,
            $this->value,
            $this->expiry
        );
    }
}