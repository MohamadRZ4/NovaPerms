<?php

namespace MohamadRZ\NovaPerms\context;

class ContextSet {

    /** @var array<string, string> */
    private array $contexts = [];

    /** @var array<string, string> */
    private array $negatedContexts = [];

    public function add(string $key, string $value, bool $negated = false): void {
        $key = strtolower(trim($key));
        $value = trim($value);

        if ($key === '' || $value === '') return;

        if ($negated) {
            $this->negatedContexts[$key] = $value;
        } else {
            $this->contexts[$key] = $value;
        }
    }

    public function has(string $key, string $value): bool {
        return ($this->contexts[$key] ?? null) === $value;
    }

    public function hasNegated(string $key, string $value): bool {
        return ($this->negatedContexts[$key] ?? null) === $value;
    }

    public function remove(string $key): void {
        unset($this->contexts[$key], $this->negatedContexts[$key]);
    }

    public function all(): array {
        return $this->contexts;
    }

    public function allNegated(): array {
        return $this->negatedContexts;
    }

    public function isEmpty(): bool {
        return empty($this->contexts) && empty($this->negatedContexts);
    }
}
