<?php

namespace MohamadRZ\NovaPerms\node;

use MohamadRZ\NovaPerms\context\Context;
use MohamadRZ\NovaPerms\utils\Duration;

abstract class AbstractNodeBuilder
{

    private string $key;
    private bool $value = true;
    private int $expiry = -1;
    private Context $context;


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