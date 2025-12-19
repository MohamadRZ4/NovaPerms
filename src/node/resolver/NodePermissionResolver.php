<?php

namespace MohamadRZ\NovaPerms\node\resolver;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

class NodePermissionResolver {
    private array $nodes;
    private array $groupPermissionsMap;
    private array $groupInheritanceMap;
    private array $allKnownPermissions;
    private ?string $name;

    private const PRIORITY_USER_EXPLICIT = 1000;
    private const PRIORITY_USER_NEGATED = 900;
    private const PRIORITY_GROUP_BASE = 100;
    private const PRIORITY_INHERITED = 50;

    public function __construct(
        array $nodes,
        array $groupPermissionsMap,
        array $groupInheritanceMap,
        array $allKnownPermissions,
        ?string $name
    ){
        $this->nodes = $nodes;
        $this->groupPermissionsMap = $groupPermissionsMap;
        $this->groupInheritanceMap = $groupInheritanceMap;
        $this->allKnownPermissions = $allKnownPermissions;
        $this->name = $name;
    }

    public function resolve(): void {
        $startTime = microtime(true);
        $logger = NovaPermsPlugin::getInstance()->getLogger();

        $logger->info("§e[Resolver] Processing permissions for: §f" . ($this->name ?? "Unknown"));

        $permissionMap = $this->collectAllPermissionsWithPriority();

        $resolved = $this->resolveConflicts($permissionMap);

        $negations = $this->extractNegations($resolved);

        $logger->info("§e[Expansion] Checking wildcards for " . count($resolved) . " nodes...");
        $expanded = $this->expandWildcardsAndRegex($resolved, $negations);

        $final = $this->applyNegationsAsExplicitFalse($expanded, $negations);

        $this->applyToPlayer($final);

        $duration = round((microtime(true) - $startTime) * 1000, 3);
        $logger->info("§a[Success] Resolution finished in {$duration}ms with " . count($final) . " final permissions.");
    }

    /**
     * @return array<string, array{value: bool, priority: int, source: string, negated: bool}>
     */
    private function collectAllPermissionsWithPriority(): array {
        $logger = NovaPermsPlugin::getInstance()->getLogger();
        $permissionMap = [];

        $logger->info("§b[Phase 1] Processing direct user permissions...");
        foreach ($this->nodes as $node) {
            if ($node instanceof InheritanceNode) {
                continue;
            }

            $key = $node->getKey();
            $value = $node->getValue();
            $isNegated = method_exists($node, 'isNegated') && $node->isNegated();

            $priority = $isNegated ? self::PRIORITY_USER_NEGATED : self::PRIORITY_USER_EXPLICIT;

            $permissionMap[$key] = [
                'value' => $value,
                'priority' => $priority,
                'source' => 'USER_DIRECT',
                'negated' => $isNegated
            ];

            $logger->info("§a  + User Direct: §f{$key} = " .
                ($value ? "true" : "false") .
                ($isNegated ? " [NEGATED]" : "") .
                " (Priority: {$priority})");
        }

        $logger->info("§b[Phase 2] Processing group inheritances...");
        $groupHierarchy = $this->buildGroupHierarchy();

        foreach ($groupHierarchy as $groupInfo) {
            $groupName = $groupInfo['name'];
            $depth = $groupInfo['depth'];

            if (!isset($this->groupPermissionsMap[$groupName])) {
                continue;
            }

            $logger->info("§6  [Group] Processing: §f{$groupName} at depth {$depth}");

            foreach ($this->groupPermissionsMap[$groupName] as $node) {
                if ($node instanceof InheritanceNode) {
                    continue;
                }

                $key = $node->getKey();
                $value = $node->getValue();
                $isNegated = method_exists($node, 'isNegated') && $node->isNegated();

                $priority = self::PRIORITY_GROUP_BASE - ($depth * 10);

                if ($isNegated) {
                    $priority += 50;
                }

                if (!isset($permissionMap[$key]) || $permissionMap[$key]['priority'] < $priority) {
                    $permissionMap[$key] = [
                        'value' => $value,
                        'priority' => $priority,
                        'source' => "GROUP_{$groupName}",
                        'negated' => $isNegated
                    ];

                    $logger->info("§d    + {$key} = " .
                        ($value ? "true" : "false") .
                        ($isNegated ? " [NEGATED]" : "") .
                        " (Priority: {$priority}, Depth: {$depth})");
                } else {
                    $logger->info("§7    - Skipped {$key} (lower priority: {$priority} vs {$permissionMap[$key]['priority']})");
                }
            }
        }

        return $permissionMap;
    }

    private function buildGroupHierarchy(): array {
        $logger = NovaPermsPlugin::getInstance()->getLogger();
        $hierarchy = [];
        $visited = [];
        $queue = [];

        foreach ($this->nodes as $node) {
            if ($node instanceof InheritanceNode) {
                $queue[] = ['name' => $node->getGroup(), 'depth' => 0];
                $logger->info("§b  Root Group: §f{$node->getGroup()}");
            }
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
                        $logger->info("§d    -> Inheritance: §f{$groupName} extends {$parentGroup}");
                    }
                }
            }
        }

        return $hierarchy;
    }

    private function resolveConflicts(array $permissionMap): array {
        $logger = NovaPermsPlugin::getInstance()->getLogger();
        $logger->info("§b[Phase 3] Resolving conflicts...");

        $resolved = [];
        $conflicts = [];

        foreach ($permissionMap as $key => $data) {
            if (!isset($resolved[$key])) {
                $resolved[$key] = $data;
            } else {
                if ($data['priority'] > $resolved[$key]['priority']) {
                    $conflicts[] = [
                        'key' => $key,
                        'old_source' => $resolved[$key]['source'],
                        'old_value' => $resolved[$key]['value'],
                        'new_source' => $data['source'],
                        'new_value' => $data['value']
                    ];
                    $resolved[$key] = $data;
                }
            }
        }

        if (!empty($conflicts)) {
            $logger->info("§c  Found " . count($conflicts) . " conflicts:");
            foreach ($conflicts as $conflict) {
                $logger->info("§c    - {$conflict['key']}: " .
                    "{$conflict['old_source']}(" . ($conflict['old_value'] ? "true" : "false") . ") " .
                    "→ {$conflict['new_source']}(" . ($conflict['new_value'] ? "true" : "false") . ")");
            }
        }

        return $resolved;
    }

    /**
     * @return array<string, array{priority: int, source: string}>
     */
    private function extractNegations(array $resolved): array {
        $logger = NovaPermsPlugin::getInstance()->getLogger();
        $logger->info("§b[Phase 4] Extracting negations...");

        $negations = [];

        foreach ($resolved as $key => $data) {
            if ($data['negated']) {
                $negations[$key] = [
                    'priority' => $data['priority'],
                    'source' => $data['source']
                ];
                $logger->info("§c  ! Found negation: §f{$key} (from {$data['source']}, priority: {$data['priority']})");
            }
        }

        return $negations;
    }

    private function expandWildcardsAndRegex(array $resolved, array $negations): array {
        $expanded = [];
        $wildcards = [];
        $logger = NovaPermsPlugin::getInstance()->getLogger();

        foreach ($resolved as $key => $data) {
            if (str_ends_with($key, '.*') || (str_starts_with($key, '/') && str_ends_with($key, '/'))) {
                $wildcards[$key] = $data;
            } else if (!$data['negated']) {
                $expanded[$key] = $data['value'];
            }
        }

        foreach ($wildcards as $perm => $data) {
            $count = 0;
            $blocked = 0;

            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                foreach ($this->allKnownPermissions as $known) {
                    if (str_starts_with($known, $prefix . '.')) {
                        if (isset($negations[$known])) {
                            $blocked++;
                            $logger->info("§c    × Blocked expansion: §f{$known} (negated by {$negations[$known]['source']})");
                            continue;
                        }

                        if (!isset($expanded[$known])) {
                            $expanded[$known] = $data['value'];
                            $count++;
                        }
                    }
                }
                $logger->info("§5  - Wildcard '{$perm}': §fExpanded to {$count} permissions (blocked: {$blocked})");

            } elseif (str_starts_with($perm, '/') && str_ends_with($perm, '/')) {
                foreach ($this->allKnownPermissions as $known) {
                    if (@preg_match($perm, $known) === 1) {
                        if (isset($negations[$known])) {
                            $blocked++;
                            $logger->info("§c    × Blocked expansion: §f{$known} (negated)");
                            continue;
                        }

                        if (!isset($expanded[$known])) {
                            $expanded[$known] = $data['value'];
                            $count++;
                        }
                    }
                }
                $logger->info("§5  - Regex '{$perm}': §fMatched {$count} permissions (blocked: {$blocked})");
            }
        }

        return $expanded;
    }

    private function applyNegationsAsExplicitFalse(array $expanded, array $negations): array {
        $logger = NovaPermsPlugin::getInstance()->getLogger();
        $logger->info("§b[Phase 5] Applying negations as explicit false...");

        $final = $expanded;
        $appliedCount = 0;

        foreach ($negations as $key => $negData) {
            $final[$key] = false;
            $appliedCount++;
            $logger->info("§c  ✗ Set to false: §f{$key} (negated by {$negData['source']})");
        }

        $logger->info("§c  Total negations applied: {$appliedCount}");

        return $final;
    }

    private function applyToPlayer(array $result): void {
        if ($this->name === null) return;

        $user = NovaPermsPlugin::getUserManager()->getUser($this->name);
        if ($user === null) return;

        $attachment = $user->getAttachment();
        if ($attachment === null) return;

        $attachment->clearPermissions();

        $logger = NovaPermsPlugin::getInstance()->getLogger();
        $logger->info("§b[Phase 6] Applying " . count($result) . " permissions to player...");

        $trueCount = 0;
        $falseCount = 0;

        foreach ($result as $perm => $value) {
            $attachment->setPermission($perm, $value);
            if ($value) {
                $trueCount++;
            } else {
                $falseCount++;
            }
        }

        $logger->info("§a  ✓ Applied: {$trueCount} true, {$falseCount} false");
    }
}