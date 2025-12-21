<?php

namespace MohamadRZ\NovaPerms\graph;

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