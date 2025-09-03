<?php

namespace MohamadRZ\NovaPerms\node;

use MohamadRZ\NovaPerms\context\BaseContextSet;

interface NodeInterface
{
    public function getKey(): string;
    public function getValue(): bool;
    public function getExpiry(): int;

    public function toNodeString(): string;
    public function getType(): string;

    /** @return AbstractNodeBuilder */
    public function toBuilder();
}