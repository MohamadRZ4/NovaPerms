<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\node\Node;
use MohamadRZ\NovaPerms\node\NodeBuilder;

class PrefixNode extends Node
{
    private string $prefix;
    private int $priority;

    public function __construct(
        string $prefix,
        int $priority,
        ContextSet $contextSet,
        bool $value = true,
        int $expiry = -1
    ) {
        parent::__construct("prefix.{$priority}.{$prefix}", $contextSet, $value, $expiry);
        $this->prefix = $prefix;
        $this->priority = $priority;
    }
    public static function builder(string $prefix, int $priority): PrefixNodeBuilder
    {
        return new PrefixNodeBuilder($prefix, $priority);
    }
    public function getType(): string { return 'prefix'; }
    public function getPrefix(): string { return $this->prefix; }
    public function getPriority(): int { return $this->priority; }
    public function toNodeString(): string
    {
        return "prefix.{$this->priority}.{$this->prefix}";
    }
    public function toBuilder(): PrefixNodeBuilder
    {
        return (new PrefixNodeBuilder($this->prefix, $this->priority))
            ->value($this->value)
            ->expiry($this->expiry)
            ->contextSet($this->contextSet);
    }
}

class PrefixNodeBuilder extends NodeBuilder
{
    private string $prefix;
    private int $priority;
    public function __construct(string $prefix, int $priority)
    {
        $this->prefix = $prefix;
        $this->priority = $priority;
        parent::__construct();
    }
    public function prefix(string $prefix): self { $this->prefix = $prefix; return $this; }
    public function priority(int $priority): self { $this->priority = $priority; return $this; }

    public function build(): PrefixNode
    {
        return new PrefixNode(
            $this->prefix,
            $this->priority,
            $this->contextSet,
            $this->value,
            $this->expiry
        );
    }
}