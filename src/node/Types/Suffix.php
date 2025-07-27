<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\Context;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class Suffix extends AbstractNode
{
    private int $priority;
    private string $suffix;

    public function __construct(string $suffix, int $priority, bool $value, int $expiry, Context $context)
    {
        $key = "suffix.$priority.$suffix";
        parent::__construct($key, $value, $expiry, $context);

        $this->suffix = $suffix;
        $this->priority = $priority;
    }

    public function getSuffix(): string
    {
        return $this->suffix;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public static function fromNodeString(string $key, bool $value, int $expiry, Context $context): ?self
    {
        if (!str_starts_with($key, 'suffix.')) return null;
        $parts = explode('.', $key, 3);
        if (count($parts) !== 3) return null;
        return new self($parts[2], (int)$parts[1], $value, $expiry, $context);
    }

    public static function builder(string $suffix, int $priority): AbstractNodeBuilder
    {
        return new Builder($suffix, $priority);
    }
}


class Builder extends AbstractNodeBuilder
{
    private $priority;
    private $suffix;
    public function __construct(string $suffix, int $priority)
    {
        $this->suffix = $suffix;
        $this->priority = $priority;
    }

    /**
     * @return self
     */
    public function suffix($prefix): self
    {
        $this->suffix = $prefix;
        return $this;
    }

    /**
     * @return self
     */
    public function priority($value): self
    {
        $this->priority = $value;
        return $this;
    }

    public function build(): AbstractNode
    {
        return new Suffix($this->suffix, $this->priority, $this->getValue(), $this->getExpiry(), $this->getContext());
    }
}