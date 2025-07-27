<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\Context;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class Inheritance extends AbstractNode
{
    private string $group;

    public function __construct(string $group, bool $value, int $expiry, Context $context)
    {
        $key = "group.$group";
        parent::__construct($key, $value, $expiry, $context);

        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    public static function fromNodeString(string $key, bool $value, int $expiry, Context $context): ?self
    {
        if (!str_starts_with($key, 'group.')) return null;
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2) return null;
        return new self($parts[1], $value, $expiry, $context);
    }

    public static function builder($group): AbstractNodeBuilder
    {
        return new Builder($group);
    }
}


class Builder extends AbstractNodeBuilder
{
    private $group;
    public function __construct(string $group)
    {
        $this->group = $group;
    }

    /**
     * @return self
     */
    public function group($group): self
    {
        $this->group = $group;
        return $this;
    }


    public function build(): AbstractNode
    {
        return new Inheritance($this->group, $this->getValue(), $this->getExpiry(), $this->getContext());
    }
}