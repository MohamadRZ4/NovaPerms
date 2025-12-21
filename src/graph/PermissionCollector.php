<?php

namespace MohamadRZ\NovaPerms\graph;

use MohamadRZ\NovaPerms\node\Types\InheritanceNode;

class PermissionCollector {
    private array $userNodes;
    private array $groupPermissionsMap;
    private array $groupInheritanceMap;
    private ?string $primaryGroup;
    private ResolverConfig $config;

    private const PRIORITY_USER = 1000;
    private const PRIORITY_PRIMARY = 500;
    private const PRIORITY_GROUP = 100;

    public function __construct(
        array $userNodes,
        array $groupPermissionsMap = [],
        array $groupInheritanceMap = [],
        ?string $primaryGroup = null,
        ?ResolverConfig $config = null
    ) {
        $this->userNodes = $userNodes;
        $this->groupPermissionsMap = $groupPermissionsMap;
        $this->groupInheritanceMap = $groupInheritanceMap;
        $this->primaryGroup = $primaryGroup;
        $this->config = $config ?? ResolverConfig::full();
    }

    /**
     * @return ResolvedNode[]
     */
    public function build(): array {
        $nodes = [];

        if ($this->config->includeUser) {
            $nodes = array_merge($nodes, $this->collectUserNodes());
        }

        if ($this->config->includeGroups) {
            $hierarchy = $this->buildHierarchy();
            $nodes = array_merge($nodes, $this->collectGroupNodes($hierarchy));
        }

        return $nodes;
    }

    private function collectUserNodes(): array {
        $nodes = [];

        foreach ($this->userNodes as $node) {
            if ($this->config->filter && !($this->config->filter)($node)) {
                continue;
            }

            $nodes[] = new ResolvedNode(
                $node->getKey(),
                $node instanceof InheritanceNode ? $node->getGroup() : $node->getValue(),
                'USER',
                self::PRIORITY_USER,
                0
            );
        }

        return $nodes;
    }

    private function collectGroupNodes(array $hierarchy): array {
        $nodes = [];

        foreach ($hierarchy as $info) {
            $groupName = $info['name'];
            $depth = $info['depth'];

            if ($this->config->maxDepth !== null && $depth > $this->config->maxDepth) {
                continue;
            }

            if (!isset($this->groupPermissionsMap[$groupName])) {
                continue;
            }

            $isPrimary = $this->primaryGroup === $groupName;
            $priority = $isPrimary ? self::PRIORITY_PRIMARY : (self::PRIORITY_GROUP - $depth * 10);

            foreach ($this->groupPermissionsMap[$groupName] as $node) {
                if ($this->config->filter && !($this->config->filter)($node)) {
                    continue;
                }

                $nodes[] = new ResolvedNode(
                    $node->getKey(),
                    $node instanceof InheritanceNode ? $node->getGroup() : $node->getValue(),
                    "GROUP_{$groupName}",
                    $priority,
                    $depth
                );
            }
        }

        return $nodes;
    }

    private function buildHierarchy(): array {
        $hierarchy = [];
        $visited = [];
        $queue = [];

        foreach ($this->userNodes as $node) {
            if ($node instanceof InheritanceNode) {
                $queue[] = ['name' => $node->getGroup(), 'depth' => 0];
            }
        }

        if (!$this->config->traverseInheritance) {
            return array_map(fn($q) => $q, $queue);
        }

        while (!empty($queue)) {
            $current = array_shift($queue);
            $groupName = $current['name'];
            $depth = $current['depth'];

            if (isset($visited[$groupName])) {
                continue;
            }

            $visited[$groupName] = true;
            $hierarchy[] = ['name' => $groupName, 'depth' => $depth];

            if (isset($this->groupInheritanceMap[$groupName])) {
                foreach ($this->groupInheritanceMap[$groupName] as $parentNode) {
                    $parentGroup = $parentNode->getGroup();
                    if (!isset($visited[$parentGroup])) {
                        $queue[] = ['name' => $parentGroup, 'depth' => $depth + 1];
                    }
                }
            }
        }

        return $hierarchy;
    }
}