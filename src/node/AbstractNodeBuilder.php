<?php

namespace MohamadRZ\NovaPerms\node;

use MohamadRZ\NovaPerms\context\Context;
use MohamadRZ\NovaPerms\utils\Duration;

abstract class AbstractNodeBuilder
{

    public string $key;
    public bool $value = true;
    public int $expiry = -1;
    public Context $context;


    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return int
     */
    public function getExpiry(): int
    {
        return $this->expiry;
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

    /**
     * @param int $expiry
     */
    public function expiry(int $expiry): self
    {
        $this->expiry = $expiry;
        return $this;
    }

    /**
     * @param string $key
     */
    public function key(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param bool $value
     */
    public function value(bool $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param Context $context
     */
    public function withContext(Context $context): void
    {
        $this->context = $context;
    }

    public function getDurationExpiry(): bool
    {
        if ((new Duration(time()))->isExpired($this->expiry)) {
            return true;
        }
        return false;
    }

    abstract public function build(): AbstractNode;
}