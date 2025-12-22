<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\node\Node;
use MohamadRZ\NovaPerms\node\NodeBuilder;

class RegexPermission extends Node
{
    public static function builder(string $pattern): RegexNodeBuilder
    {
        return new RegexNodeBuilder($pattern);
    }

    public function getType(): string
    {
        return 'regex';
    }

    public function toNodeString(): string
    {
        return $this->key;
    }

    public function toBuilder(): RegexNodeBuilder
    {
        return (new RegexNodeBuilder($this->key))
            ->value($this->value)
            ->expiry($this->expiry)
            ->contextSet($this->contextSet);
    }
}

class RegexNodeBuilder extends NodeBuilder
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
        parent::__construct();
    }

    public function pattern(string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function build(): RegexPermission
    {
        return new RegexPermission(
            $this->pattern,
            $this->contextSet,
            $this->value,
            $this->expiry
        );
    }
}