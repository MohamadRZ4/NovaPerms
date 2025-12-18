<?php

namespace MohamadRZ\NovaPerms\context;

class ImmutableContextSet implements ContextSet {

    /** @var array<string, string> */
    private array $contexts;

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
        throw new \LogicException("ImmutableContextSet is read-only.");
    }

    public function remove(string $key): void {
        throw new \LogicException("ImmutableContextSet is read-only.");
    }

    public function isEmpty(): bool {
        return empty($this->contexts);
    }

    public function toMutable(): MutableContextSet {
        return new MutableContextSet($this->contexts);
    }

    public function toArray(): array {
        return $this->contexts;
    }
}
