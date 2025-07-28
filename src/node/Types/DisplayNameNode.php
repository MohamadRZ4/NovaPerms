<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class DisplayNameNode extends AbstractNode
{
    private string $displayname;

    public function __construct(
        string $displayname,
        bool $value = true,
        int $expiry = -1,
        ?BaseContextSet $context = null
    ) {
        parent::__construct("displayname.{$displayname}", $value, $expiry, $context);
        $this->displayname = $displayname;
    }
    public static function builder(string $group): DisplayNameNodeBuilder
    {
        return new DisplayNameNodeBuilder($group);
    }
    public function getType(): string { return 'displayname'; }
    public function getDisplayName(): string { return $this->displayname; }
    public function toNodeString(): string
    {
        return "displayname.{$this->displayname}";
    }
    public function toBuilder(): DisplayNameNodeBuilder
    {
        return new DisplayNameNodeBuilder($this->displayname)
            ->value($this->value)
            ->expiry($this->expiry)
            ->withContext($this->context);
    }
}

class DisplayNameNodeBuilder extends AbstractNodeBuilder
{
    private string $displayName;
    public function __construct(string $displayName) { $this->displayName = $displayName; }
    public function displayName(string $displayName): self { $this->displayName = $displayName; return $this; }
    public function build(): DisplayNameNode
    {
        return new DisplayNameNode(
            $this->displayName,
            $this->value,
            $this->expiry,
            $this->context
        );
    }
}