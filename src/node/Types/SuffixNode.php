<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class SuffixNode extends AbstractNode
{
    private string $suffix;
    private int $priority;

    public function __construct(
        string $suffix,
        int $priority,
        bool $value = true,
        int $expiry = -1,
        ?BaseContextSet $context = null
    ) {
        parent::__construct("suffix.{$priority}.{$suffix}", $value, $expiry, $context);
        $this->suffix = $suffix;
        $this->priority = $priority;
    }
    public static function builder(string $suffix, int $priority): SuffixNodeBuilder
    {
        return new SuffixNodeBuilder($suffix, $priority);
    }
    public function getType(): string { return 'suffix'; }
    public function getSuffix(): string { return $this->suffix; }
    public function getPriority(): int { return $this->priority; }
    public function toNodeString(): string
    {
        return "suffix.{$this->priority}.{$this->suffix}";
    }
    public function toBuilder(): SuffixNodeBuilder
    {
        return new SuffixNodeBuilder($this->suffix, $this->priority)
            ->value($this->value)
            ->expiry($this->expiry);
    }
}

class SuffixNodeBuilder extends AbstractNodeBuilder
{
    private string $suffix;
    private int $priority;
    public function __construct(string $suffix, int $priority)
    {
        $this->suffix = $suffix;
        $this->priority = $priority;
    }
    public function suffix(string $suffix): self { $this->suffix = $suffix; return $this; }
    public function priority(int $priority): self { $this->priority = $priority; return $this; }

    public function build(): SuffixNode
    {
        return new SuffixNode(
            $this->suffix,
            $this->priority,
            $this->value,
            $this->expiry
        );
    }
}