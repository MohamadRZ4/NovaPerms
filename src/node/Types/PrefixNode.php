<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class PrefixNode extends AbstractNode
{
    private string $prefix;
    private int $priority;

    public function __construct(
        string $prefix,
        int $priority,
        bool $value = true,
        int $expiry = -1,
        ?BaseContextSet $context = null
    ) {
        parent::__construct("prefix.{$priority}.{$prefix}", $value, $expiry, $context);
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
        return new PrefixNodeBuilder($this->prefix, $this->priority)
            ->value($this->value)
            ->expiry($this->expiry)
            ->withContext($this->context);
    }
}

class PrefixNodeBuilder extends AbstractNodeBuilder
{
    private string $prefix;
    private int $priority;
    public function __construct(string $prefix, int $priority)
    {
        $this->prefix = $prefix;
        $this->priority = $priority;
    }
    public function prefix(string $prefix): self { $this->prefix = $prefix; return $this; }
    public function priority(int $priority): self { $this->priority = $priority; return $this; }

    public function build(): PrefixNode
    {
        return new PrefixNode(
            $this->prefix,
            $this->priority,
            $this->value,
            $this->expiry,
            $this->context
        );
    }
}