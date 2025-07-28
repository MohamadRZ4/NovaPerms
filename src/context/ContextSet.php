<?php

namespace MohamadRZ\NovaPerms\context;

interface ContextSet extends \Countable
{
    public function isEmpty(): bool;
    public function size(): int;
    public function getContexts(): array;
    public function containsKey(string $key): bool;
    public function getValues(string $key): array;
    public function contains(Context $context): bool;
    public function toMap(): array;
    public function immutableCopy(): ImmutableContextSet;
    public function mutableCopy(): MutableContextSet;
    public function count(): int;
}
