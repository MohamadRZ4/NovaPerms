<?php

namespace MohamadRZ\NovaPerms\context;

class MutableContextSet implements ContextSet {

    /** @var array<string, string> */
    private array $contexts = [];

    public function __construct(array $contexts = []) {
        $this->contexts = $contexts;
    }

    public static function of(string $key, string $value): self {
        return new self([$key => $value]);
    }

    public function getAll(): array {
        return $this->contexts;
    }

    public function has(string $key, string $value): bool {
        return isset($this->contexts[$key]) && $this->contexts[$key] === $value;
    }

    public function add(string $key, string $value): void {
        $this->contexts[$key] = $value;
    }

    public function remove(string $key): void {
        unset($this->contexts[$key]);
    }

    public function isEmpty(): bool {
        return empty($this->contexts);
    }

    public function toImmutable(): ImmutableContextSet {
        return new ImmutableContextSet($this->contexts);
    }

    public function toArray(): array {
        return $this->contexts;
    }
}
