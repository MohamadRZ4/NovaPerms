<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class DisplayNameNode extends AbstractNode
{
    private string $displayName;

    public function __construct(
        string $displayName,
        ?ContextSet $contextSet = null,
        bool $value = true,
        int $expiry = -1,
        bool $negated = false
    )
    {
        parent::__construct($displayName, $contextSet, $value, $expiry, $negated);
        $this->displayName = $displayName;

    }
    public static function builder(string $group): DisplayNameNodeBuilder
    {
        return new DisplayNameNodeBuilder($group);
    }

    public function getType(): string { return 'displayname'; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getContexts(): ContextSet { return $this->contextSet; }

    public function toNodeString(): string
    {
        return "{$this->displayName}";
    }

    public function toBuilder(): DisplayNameNodeBuilder
    {
        return (new DisplayNameNodeBuilder($this->displayName))
            ->value($this->value)
            ->expiry($this->expiry)
            ->contextSet($this->contextSet);
    }
}
class DisplayNameNodeBuilder extends AbstractNodeBuilder
{
    private string $displayName;

    public function __construct(string $displayName) {
        $this->displayName = $displayName;
        parent::__construct();
    }

    public function displayName(string $displayName): self {
        $this->displayName = $displayName;
        return $this;
    }

    public function build(): DisplayNameNode
    {
        return new DisplayNameNode(
            $this->displayName,
            $this->contextSet,
            $this->value,
            $this->expiry,
            $this->negated
        );
    }
}
