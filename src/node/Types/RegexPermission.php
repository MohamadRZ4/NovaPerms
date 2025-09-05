<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class RegexPermission extends AbstractNode
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
            ->expiry($this->expiry);
    }
}

class RegexNodeBuilder extends AbstractNodeBuilder
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
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
            $this->value,
            $this->expiry
        );
    }
}