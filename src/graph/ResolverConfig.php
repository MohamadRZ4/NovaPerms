<?php

namespace MohamadRZ\NovaPerms\graph;

use MohamadRZ\NovaPerms\node\Types\InheritanceNode;

class ResolverConfig {
    public bool $includeUser = true;
    public bool $includeGroups = true;
    public bool $traverseInheritance = true;
    public ?int $maxDepth = null;
    public ?\Closure $filter = null;

    public static function full(): self {
        return new self();
    }

    public static function userOnly(): self {
        $config = new self();
        $config->includeGroups = false;
        $config->traverseInheritance = false;
        return $config;
    }

    public static function directGroupsOnly(): self {
        $config = new self();
        $config->traverseInheritance = false;
        $config->maxDepth = 0;
        return $config;
    }

    public static function inheritanceOnly(): self {
        $config = new self();
        $config->filter = fn($node) => $node instanceof InheritanceNode;
        return $config;
    }

    public static function permissionsOnly(): self {
        $config = new self();
        $config->filter = fn($node) => !($node instanceof InheritanceNode);
        return $config;
    }

    public function withFilter(\Closure $filter): self {
        $this->filter = $filter;
        return $this;
    }

    public function withMaxDepth(int $depth): self {
        $this->maxDepth = $depth;
        return $this;
    }
}