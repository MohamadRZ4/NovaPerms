<?php

namespace MohamadRZ\NovaPerms\context;

interface ContextSet {

    /**
     * @return array<string, string>
     */
    public function getAll(): array;

    public function has(string $key, string $value): bool;

    public function add(string $key, string $value): void;

    public function remove(string $key): void;

    public function isEmpty(): bool;
}
