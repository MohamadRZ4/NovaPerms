<?php

namespace MohamadRZ\NovaPerms\node\resolver;

class PermissionTrie
{
    private array $root = [];

    public function insert(string $perm): void
    {
        $node  = &$this->root;
        $parts = explode('.', $perm);
        foreach ($parts as $part) {
            $node[$part] ??= [];
            $node = &$node[$part];
        }
        $node['*'] = true;
    }

    public function getAllWithPrefix(string $prefix): array
    {
        $node  = $this->root;
        $parts = explode('.', $prefix);

        foreach ($parts as $part) {
            if (!isset($node[$part])) {
                return [];
            }
            $node = $node[$part];
        }
        return $this->collectAll($node, $prefix);
    }

    private function collectAll(array $node, string $current): array
    {
        $result = [];
        if (isset($node['*'])) {
            $result[] = $current;
        }
        foreach ($node as $key => $child) {
            if ($key !== '*') {
                $result = array_merge($result, $this->collectAll($child, $current . '.' . $key));
            }
        }
        return $result;
    }
}