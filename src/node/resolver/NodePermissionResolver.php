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

        $collected = [];
        $negatedNodes = [];
        $visitedGroups = [];
        $stack = [];

        foreach ($this->nodes as $node) {
            /* @var $node AbstractNode */
            if ($node instanceof InheritanceNode) {
                $stack[] = ['group' => $node->getGroup(), 'depth' => 0];
                $logger->info("§b - Group Inheritance: §fFound '{$node->getGroup()}'");
            } else {
                $key = $node->getKey();
                $collected[$key] = $node->getValue();
                if (method_exists($node, 'isNegated') && $node->isNegated()) {
                    $negatedNodes[$key] = true;
                }
                $logger->info("§a - Direct Perm: §f{$key} (" . ($node->getValue() ? "true" : "false") . ")");
            }
        }

        while (!empty($stack)) {
            $item = array_pop($stack);
            $groupName = $item['group'];
            $depth = $item['depth'];

            if (isset($visitedGroups[$groupName])) continue;
            $visitedGroups[$groupName] = true;

            $logger->info("§6[Group Path] Analyzing: §f{$groupName} at depth {$depth}");

            if (isset($this->groupPermissionsMap[$groupName])) {
                foreach ($this->groupPermissionsMap[$groupName] as $perm => $value) {
                    if (!isset($collected[$perm])) {
                        $collected[$perm] = $value;
                        $logger->info("§d   + From Group: §f{$perm} (" . ($value ? "true" : "false") . ")");
                    } else {
                        if ($collected[$perm] === false && $value === true) {
                            $logger->info("§d   - Skipped override of {$perm} from group (user false)");
                            continue;
                        }
                    }
                }
            }

            if (isset($this->groupInheritanceMap[$groupName])) {
                foreach ($this->groupInheritanceMap[$groupName] as $parent) {
                    if (!isset($visitedGroups[$parent])) {
                        $stack[] = ['group' => $parent, 'depth' => $depth + 1];
                        $logger->info("§d   -> Sub-inheritance: §f'{$groupName}' inherits '{$parent}'");
                    }
                }
            }
        }

        foreach ($negatedNodes as $negKey => $_) {
            if (isset($collected[$negKey])) {
                $logger->info("§c - Negated node applied: §f{$negKey}, removing from final perms");
                unset($collected[$negKey]);
            }
        }

        $logger->info("§e[Expansion] Checking wildcards for " . count($collected) . " nodes...");
        $result = $this->expandWildcardsAndRegex($collected);

        $this->applyToPlayer($result);

        $duration = round((microtime(true) - $startTime) * 1000, 3);
        $logger->info("§a[Success] Resolution for {$this->name} finished in {$duration}ms.");
    }

    private function applyToPlayer(array $result): void {
        if ($this->name === null) return;

        $user = NovaPermsPlugin::getUserManager()->getUser($this->name);
        if ($user === null) return;

        $attachment = $user->getAttachment();
        if ($attachment === null) return;

        $attachment->clearPermissions();

        foreach ($result as $perm => $value) {
            $attachment->setPermission($perm, $value);
        }
    }

    private function expandWildcardsAndRegex(array $perms): array {
        $expanded = [];
        $wildcards = [];
        $logger = NovaPermsPlugin::getInstance()->getLogger();

        foreach ($perms as $perm => $value) {
            if (str_ends_with($perm, '.*') || (str_starts_with($perm, '/') && str_ends_with($perm, '/'))) {
                $wildcards[$perm] = $value;
            } else {
                $expanded[$perm] = $value;
            }
        }

        foreach ($wildcards as $perm => $value) {
            $count = 0;
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                foreach ($this->allKnownPermissions as $known) {
                    if (str_starts_with($known, $prefix) && !isset($expanded[$known])) {
                        $expanded[$known] = $value;
                        $count++;
                    }
                }
                $logger->info("§5 - Wildcard '{$perm}': §fExpanded to {$count} nodes.");
            } elseif (str_starts_with($perm, '/') && str_ends_with($perm, '/')) {
                foreach ($this->allKnownPermissions as $known) {
                    if (@preg_match($perm, $known) === 1 && !isset($expanded[$known])) {
                        $expanded[$known] = $value;
                        $count++;
                    }
                }
                $logger->info("§5 - Regex '{$perm}': §fMatched {$count} nodes.");
            }
        }

        return $expanded;
    }
}
