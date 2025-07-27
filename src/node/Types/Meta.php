<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\Context;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class Meta extends AbstractNode
{
    private string $metaKey;
    private string $metaValue;

    public function __construct(string $metaKey, string $metaValue, bool $value, int $expiry, Context $context)
    {
        $key = "meta.$metaKey.$metaValue";
        parent::__construct($key, $value, $expiry, $context);

        $this->metaKey   = $metaKey;
        $this->metaValue = $metaValue;
    }

    public function getMetaKey(): string
    {
        return $this->metaKey;
    }

    public function getMetaValue(): string
    {
        return $this->metaValue;
    }

    public static function fromNodeString(string $key, bool $value, int $expiry, Context $context): ?self
    {
        if (!str_starts_with($key, 'meta.')) return null;
        $parts = explode('.', $key, 3);
        if (count($parts) !== 3) return null;

        return new self($parts[1], $parts[2], $value, $expiry, $context);
    }

    public static function builder(string $metaKey, string $metaValue): AbstractNodeBuilder
    {
        return new Builder($metaKey, $metaValue);
    }
}

class Builder extends AbstractNodeBuilder
{
    private string $metaKey;
    private string $metaValue;

    public function __construct(string $metaKey, string $metaValue)
    {
        $this->metaKey = $metaKey;
        $this->metaValue = $metaValue;
    }

    public function metaKey(string $metaKey): self
    {
        $this->metaKey = $metaKey;
        return $this;
    }

    public function metaValue(string $metaValue): self
    {
        $this->metaValue = $metaValue;
        return $this;
    }

    public function build(): AbstractNode
    {
        return new Meta($this->metaKey, $this->metaValue, $this->getValue(), $this->getExpiry(), $this->getContext());
    }
}
