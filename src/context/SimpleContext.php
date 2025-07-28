<?php

namespace MohamadRZ\NovaPerms\context;

class SimpleContext implements Context
{
    private string $key;
    private string $value;

    public function __construct(string $key, string $value)
    {
        $this->key = strtolower(trim($key));
        $this->value = trim($value);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->key . '=' . $this->value;
    }

    public function equals(Context $other): bool
    {
        return $this->key === $other->getKey() && $this->value === $other->getValue();
    }
}