<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class MetaNode extends AbstractNode
{
    private string $metaKey;
    private string $metaValue;

    public function __construct(
        string $metaKey,
        string $metaValue,
        bool $value = true,
        int $expiry = -1
    ) {
        parent::__construct("meta.{$metaKey}.{$metaValue}", $value, $expiry);
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
            ->expiry($this->expiry);
    }
}

class MetaNodeBuilder extends AbstractNodeBuilder
{
    private string $metaKey;
    private string $metaValue;
    public function __construct(string $metaKey, string $metaValue)
    {
        $this->metaKey = $metaKey;
        $this->metaValue = $metaValue;
    }
    public function metaKey(string $metaKey): self { $this->metaKey = $metaKey; return $this; }
    public function metaValue(string $metaValue): self { $this->metaValue = $metaValue; return $this; }

    public function build(): MetaNode
    {
        return new MetaNode(
            $this->metaKey,
            $this->metaValue,
            $this->value,
            $this->expiry
        );
    }
}