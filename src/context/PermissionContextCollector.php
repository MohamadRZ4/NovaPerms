<?php

namespace MohamadRZ\NovaPerms\context;

class PermissionContextCollector implements ContextConsumer {

    /** @var array<string, string> */
    private array $contexts = [];

    public function accept(string $key, string $value): void {
        $key = strtolower(trim($key));
        $value = trim($value);

        if ($key === "" || $value === "") {
            return;
        }

        $this->contexts[$key] = $value;
    }

    /**
     * @return array<string, string>
     */
    public function getContexts(): array {
        return $this->contexts;
    }

    public function clear(): void {
        $this->contexts = [];
    }

    public function toImmutableSet(): ImmutableContextSet {
        return new ImmutableContextSet($this->contexts);
    }
}
