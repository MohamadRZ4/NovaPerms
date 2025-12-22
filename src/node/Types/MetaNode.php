<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\node\Node;
use MohamadRZ\NovaPerms\node\NodeBuilder;

class MetaNode extends Node
{
    private string $metaKey;
    private string $metaValue;

    public function __construct(
        string $metaKey,
        string $metaValue,
        ContextSet $contextSet,
        bool $value = true,
        int $expiry = -1
    ) {
        parent::__construct("meta.{$metaKey}.{$metaValue}", $contextSet, $value, $expiry);
        $this->metaKey = $metaKey;
        $this->metaValue = $metaValue;
    }
    public static function builder(string $metaKey, string $metaValue): MetaNodeBuilder
    {
        return new MetaNodeBuilder($metaKey, $metaValue);
    }
    public function getType(): string { return 'meta'; }
    public function getMetaKey(): string { return $this->metaKey; }
    public function getMetaValue(): string { return $this->metaValue; }
    public function toNodeString(): string
    {
        return "meta.{$this->metaKey}.{$this->metaValue}";
    }
    public function toBuilder(): MetaNodeBuilder
    {
        return (new MetaNodeBuilder($this->metaKey, $this->metaValue))
            ->value($this->value)
            ->expiry($this->expiry)
            ->contextSet($this->contextSet);
    }
}

class MetaNodeBuilder extends NodeBuilder
{
    private string $metaKey;
    private string $metaValue;
    public function __construct(string $metaKey, string $metaValue)
    {
        $this->metaKey = $metaKey;
        $this->metaValue = $metaValue;
        parent::__construct();
    }
    public function metaKey(string $metaKey): self { $this->metaKey = $metaKey; return $this; }
    public function metaValue(string $metaValue): self { $this->metaValue = $metaValue; return $this; }

    public function build(): MetaNode
    {
        return new MetaNode(
            $this->metaKey,
            $this->metaValue,
            $this->contextSet,
            $this->value,
            $this->expiry
        );
    }
}