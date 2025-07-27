<?php

namespace MohamadRZ\NovaPerms\node;

use MohamadRZ\NovaPerms\context\Context;

abstract class AbstractNode
{

    public string $key;
    public bool $value;
    public int $expiry;
    public Context $context;

    public function __construct(string $key, bool $value, int $expiry, Context $context)
    {
        $this->key = $key;
        $this->value = $value;
        $this->expiry = $expiry;
        $this->context = $context;
    }

    /**
     * @return int
     */
    public function getExpiry(): int
    {
        return $this->expiry;
    }

    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return bool
     */
    public function getValue(): bool
    {
        return $this->value;
    }
}