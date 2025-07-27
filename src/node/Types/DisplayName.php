<?php

namespace MohamadRZ\NovaPerms\node\Types;

use MohamadRZ\NovaPerms\context\Context;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\AbstractNodeBuilder;

class DisplayName extends AbstractNode
{
    private string $displayName;

    public function __construct(string $displayName, bool $value, int $expiry, Context $context)
    {
        $key = "displayname.$displayName";
        parent::__construct($key, $value, $expiry, $context);

        $this->displayName = $displayName;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public static function fromNodeString(string $key, bool $value, int $expiry, Context $context): ?self
    {
        if (!str_starts_with($key, 'displayname.')) return null;
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2) return null;
        return new self($parts[1], $value, $expiry, $context);
    }

    public static function builder($displayName): AbstractNodeBuilder
    {
        return new Builder($displayName);
    }
}


class Builder extends AbstractNodeBuilder
{
    private $displayName;
    public function __construct(string $displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * @return self
     */
    public function displayName($displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }


    public function build(): AbstractNode
    {
        return new DisplayName($this->displayName, $this->getValue(), $this->getExpiry(), $this->getContext());
    }
}