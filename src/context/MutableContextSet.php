<?php

namespace MohamadRZ\NovaPerms\context;

class MutableContextSet extends BaseContextSet
{
    public static function create(): self
    {
        return new self();
    }

    public static function fromMap(array $contexts): self
    {
        $instance = new self();
        $instance->contexts = $contexts;
        return $instance;
    }

    public function add(string $key, string $value): self
    {
        $key = strtolower($key);

        if (!isset($this->contexts[$key])) {
            $this->contexts[$key] = [];
        }

        if (!in_array($value, $this->contexts[$key])) {
            $this->contexts[$key][] = $value;
        }

        return $this;
    }

    public function addContext(Context $context): self
    {
        return $this->add($context->getKey(), $context->getValue());
    }

    public function addAll(ContextSet $contextSet): self
    {
        foreach ($contextSet->getContexts() as $context) {
            $this->addContext($context);
        }
        return $this;
    }

    public function remove(string $key, string $value): self
    {
        $key = strtolower($key);

        if (isset($this->contexts[$key])) {
            $index = array_search($value, $this->contexts[$key]);
            if ($index !== false) {
                unset($this->contexts[$key][$index]);
                $this->contexts[$key] = array_values($this->contexts[$key]);

                if (empty($this->contexts[$key])) {
                    unset($this->contexts[$key]);
                }
            }
        }

        return $this;
    }

    public function removeAll(string $key): self
    {
        unset($this->contexts[strtolower($key)]);
        return $this;
    }

    public function clear(): self
    {
        $this->contexts = [];
        return $this;
    }

    public function immutableCopy(): ImmutableContextSet
    {
        return new ImmutableContextSet($this->contexts);
    }

    public function mutableCopy(): MutableContextSet
    {
        return self::fromMap($this->contexts);
    }
}
