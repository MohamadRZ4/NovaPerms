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

class ResolvedNode {
    public string $key;
    public mixed $value;
    public string $source;
    public int $priority;
    public int $depth;

    public function __construct(string $key, mixed $value, string $source, int $priority, int $depth = 0) {
        $this->key = $key;
        $this->value = $value;
        $this->source = $source;
        $this->priority = $priority;
        $this->depth = $depth;
    }
}

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

class PermissionResolver {
    private array $nodes;
    private array $groupPermissionsMap;
    private array $groupInheritanceMap;
    private array $allKnownPermissions;
    private ?string $primaryGroup;
    private ResolverConfig $config;

    public function __construct(
        array $nodes,
        array $groupPermissionsMap,
        array $groupInheritanceMap,
        array $allKnownPermissions,
        ?string $primaryGroup = null,
        ?ResolverConfig $config = null
    ){
        $this->nodes = $nodes;
        $this->groupPermissionsMap = $groupPermissionsMap;
        $this->groupInheritanceMap = $groupInheritanceMap;
        $this->allKnownPermissions = $allKnownPermissions;
        $this->primaryGroup = $primaryGroup;
        $this->config = $config ?? ResolverConfig::permissionsOnly();
    }

    /**
     * @param \Closure $onComplete function(array $permissions): void
     */
    public function resolve(\Closure $onComplete): void {
        $startTime = microtime(true);

        $graph = new PermissionCollector(
            $this->nodes,
            $this->groupPermissionsMap,
            $this->groupInheritanceMap,
            $this->primaryGroup,
            $this->config
        );

        $graphNodes = $graph->build();

        $permissionMap = [];
        foreach ($graphNodes as $node) {
            if (!isset($permissionMap[$node->key]) || $node->priority > $permissionMap[$node->key]['priority']) {
                $permissionMap[$node->key] = [
                    'value' => $node->value,
                    'priority' => $node->priority,
                    'source' => $node->source
                ];
            }
        }

        $expanded = $this->expandWildcards($permissionMap);

        $duration = round((microtime(true) - $startTime) * 1000, 3);

        $onComplete($expanded);
    }

    /**
     * @return array<string, bool>
     */
    public function collect(): array {
        $result = [];
        $this->resolve(function($permissions) use (&$result) {
            $result = $permissions;
        });
        return $result;
    }

    private function expandWildcards(array $permissionMap): array {
        $expanded = [];
        $wildcards = [];

        foreach ($permissionMap as $key => $data) {
            if (str_ends_with($key, '.*') || (str_starts_with($key, '/') && str_ends_with($key, '/'))) {
                $wildcards[$key] = $data;
            } else {
                $expanded[$key] = $data['value'];
            }
        }

        foreach ($wildcards as $perm => $data) {
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                foreach ($this->allKnownPermissions as $known) {
                    if (str_starts_with($known, $prefix . '.') && !isset($expanded[$known])) {
                        $expanded[$known] = $data['value'];
                    }
                }
            } elseif (str_starts_with($perm, '/') && str_ends_with($perm, '/')) {
                foreach ($this->allKnownPermissions as $known) {
                    if (@preg_match($perm, $known) === 1 && !isset($expanded[$known])) {
                        $expanded[$known] = $data['value'];
                    }
                }
            }
        }

        return $expanded;
    }
}