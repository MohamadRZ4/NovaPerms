<?php

namespace MohamadRZ\NovaPerms\context;

abstract class BaseContextSet implements ContextSet
{
    protected array $contexts = [];

    public function isEmpty(): bool
    {
        return empty($this->contexts);
    }

    public function size(): int
    {
        return array_sum(array_map('count', $this->contexts));
    }

    public function getContexts(): array
    {
        $result = [];
        foreach ($this->contexts as $key => $values) {
            foreach ($values as $value) {
                $result[] = new SimpleContext($key, $value);
            }
        }
        return $result;
    }

    public function containsKey(string $key): bool
    {
        return isset($this->contexts[strtolower($key)]);
    }

    public function getValues(string $key): array
    {
        return $this->contexts[strtolower($key)] ?? [];
    }

    public function contains(Context $context): bool
    {
        $key = strtolower($context->getKey());
        return isset($this->contexts[$key]) && in_array($context->getValue(), $this->contexts[$key]);
    }

    public function toMap(): array
    {
        return $this->contexts;
    }

    public function count(): int
    {
        return $this->size();
    }
}
