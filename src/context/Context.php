<?php

namespace MohamadRZ\NovaPerms\context;

interface Context
{
    public function getKey(): string;
    public function getValue(): string;
    public function __toString(): string;
}