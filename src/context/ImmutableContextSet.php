<?php

namespace MohamadRZ\NovaPerms\context;

class ImmutableContextSet extends BaseContextSet
{
    public function __construct(array $contexts)
    {
        $this->contexts = $contexts;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function of(string $key, string $value): self
    {
        return new self([strtolower($key) => [$value]]);
    }

    public static function builder(): ImmutableContextSetBuilder
    {
        return new ImmutableContextSetBuilder();
    }

    public static function fromContexts(array $contexts): self
    {
        $map = [];
        foreach ($contexts as $context) {
            if ($context instanceof Context) {
                $key = strtolower($context->getKey());
                if (!isset($map[$key])) {
                    $map[$key] = [];
                }
                if (!in_array($context->getValue(), $map[$key])) {
                    $map[$key][] = $context->getValue();
                }
            }
        }
        return new self($map);
    }

    public function with(string $key, string $value): self
    {
        $newContexts = $this->contexts;
        $key = strtolower($key);

        if (!isset($newContexts[$key])) {
            $newContexts[$key] = [];
        }

        if (!in_array($value, $newContexts[$key])) {
            $newContexts[$key][] = $value;
        }

        return new self($newContexts);
    }

    public function without(string $key, ?string $value = null): self
    {
        $newContexts = $this->contexts;
        $key = strtolower($key);

        if ($value === null) {
            unset($newContexts[$key]);
        } else {
            if (isset($newContexts[$key])) {
                $index = array_search($value, $newContexts[$key]);
                if ($index !== false) {
                    unset($newContexts[$key][$index]);
                    $newContexts[$key] = array_values($newContexts[$key]);

                    if (empty($newContexts[$key])) {
                        unset($newContexts[$key]);
                    }
                }
            }
        }

        return new self($newContexts);
    }


    public function immutableCopy(): ImmutableContextSet
    {
        return $this;
    }

    public function mutableCopy(): MutableContextSet
    {
        return MutableContextSet::fromMap($this->contexts);
    }

    public function toArray(): array
    {
        return $this->contexts;
    }

}
