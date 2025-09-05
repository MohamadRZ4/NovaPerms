<?php

namespace MohamadRZ\NovaPerms\node\resolver;

use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\node\Types\PermissionNode;
use MohamadRZ\NovaPerms\node\Types\RegexPermission;

class NodePermissionResolver
{
    public const int LEVEL_BASIC    = 0;
    public const int LEVEL_WILDCARD = 1;
    public const int LEVEL_REGEX    = 2;

    /** @var string[] */
    private array $knownPermissions;

    private PermissionTrie $permissionTrie;

    public function __construct(array $knownPermissions = [])
    {
        $this->knownPermissions = $knownPermissions;
        $this->permissionTrie   = new PermissionTrie();

        foreach ($knownPermissions as $perm) {
            $this->permissionTrie->insert($perm);
        }
    }

    private function logDebug(string $message, mixed $data = null): void
    {
        echo "[NodePermissionResolver] " . $message . PHP_EOL;
        if ($data !== null) {
            var_dump($data);
        }
    }

    public function searchFromNodes(array $nodes, GroupManager $manager, int $level = self::LEVEL_BASIC): array
    {
        $this->logDebug("Starting searchFromNodes, level = {$level}");
        $this->logDebug("Initial nodes:", $nodes);

        $visitedGroups = [];
        $collected     = $this->traverseNodes($nodes, $manager, $level, $visitedGroups);

        if ($level >= self::LEVEL_WILDCARD) {
            $this->logDebug("Expanding wildcards...");
            $collected = $this->expandWildcardsWithTrie($collected);
        }

        $this->logDebug("Final collected permissions:", array_keys($collected));
        return $collected;
    }

    private function traverseNodes(array $nodes, GroupManager $manager, int $level, array &$visitedGroups): array
    {
        $this->logDebug("Entering traverseNodes, " . count($nodes) . " node(s) to process.");
        $collected = [];
        $stack     = [$nodes];

        while ($stack) {
            $currentNodes = array_pop($stack);

            foreach ($currentNodes as $node) {
                if ($node instanceof InheritanceNode) {
                    $groupName = $node->getGroup();
                    $this->logDebug("Found InheritanceNode for group: {$groupName}");
                    if (!isset($visitedGroups[$groupName])) {
                        $visitedGroups[$groupName] = true;
                        $group = $manager->getGroup($groupName);
                        $this->logDebug("Group lookup result for '{$groupName}':", $group);
                        if ($group !== null) {
                            $permissionNodes = $group->getOwnPermissionNodes();
                            $this->logDebug("Group '{$groupName}' own permissions:", $permissionNodes);
                            $stack[] = $permissionNodes;
                        }
                    }
                    continue;
                }

                if ($node instanceof PermissionNode) {
                    $this->logDebug("Adding PermissionNode: {$node->getKey()} => " . var_export($node->getValue(), true));
                    $collected[$node->getKey()] = $node->getValue();
                    continue;
                }

                if ($level >= self::LEVEL_REGEX && $node instanceof RegexPermission) {
                    $this->logDebug("Evaluating RegexPermission: {$node->getKey()}");
                    foreach ($this->knownPermissions as $known) {
                        if (@preg_match($node->getKey(), $known)) {
                            $this->logDebug("Regex matched: {$known}");
                            $collected[$known] = $node->getValue();
                        }
                    }
                }
            }
        }

        return $collected;
    }


    private function expandWildcardsWithTrie(array $perms): array
    {
        $expanded = $perms;

        foreach ($perms as $perm => $value) {
            if (str_ends_with($perm, '.*')) {
                $prefix        = substr($perm, 0, -2);
                foreach ($this->permissionTrie->getAllWithPrefix($prefix) as $known) {
                    $expanded[$known] = $value;
                }
            }
        }

        return $expanded;
    }
}
