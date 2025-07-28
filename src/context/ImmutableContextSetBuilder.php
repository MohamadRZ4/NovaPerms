<?php

namespace MohamadRZ\NovaPerms\context;

class ImmutableContextSetBuilder
{
    private array $contexts = [];

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

    public function build(): ImmutableContextSet
    {
        return new ImmutableContextSet($this->contexts);
    }
}
