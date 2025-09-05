<?php

namespace MohamadRZ\NovaPerms\node\resolver;

use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\node\Types\PermissionNode;
use MohamadRZ\NovaPerms\node\Types\RegexPermission;

class NodePermissionResolver
{
    public const int LEVEL_BASIC = 0;
    public const int LEVEL_REGEX = 1;

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

    public function searchFromNodes(array $nodes, GroupManager $manager, int $level = self::LEVEL_BASIC): array
    {

        $visitedGroups = [];
        $collected     = $this->traverseNodes($nodes, $manager, $level, $visitedGroups);

        if ($level >= self::LEVEL_REGEX) {
            $collected = $this->expandWildcardsWithTrie($collected);
        }

        return $collected;
    }

    private function traverseNodes(array $nodes, GroupManager $manager, int $level, array &$visitedGroups): array
    {
        $collected = [];
        $stack     = [$nodes];

        while ($stack) {
            $currentNodes = array_pop($stack);

            foreach ($currentNodes as $node) {
                if ($node instanceof InheritanceNode) {
                    $groupName = $node->getGroup();
                    $collected[$node->getKey()] = $node->getValue();

                    if (!isset($visitedGroups[$groupName])) {
                        $visitedGroups[$groupName] = true;
                        $group = $manager->getGroup($groupName);
                        if ($group !== null) {
                            $stack[] = $group->getOwnPermissionNodes();
                        }
                    }
                    continue;
                }

                if ($node instanceof PermissionNode) {
                    $collected[$node->getKey()] = $node->getValue();
                    continue;
                }
 
                if ($level >= self::LEVEL_REGEX && $node instanceof RegexPermission) {
                    $pattern = $node->getKey();

                    if (str_contains($pattern, '*') && ($pattern[0] !== '/' || substr($pattern, -1) !== '/')) {
                        $pattern = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i';
                    }

                    foreach ($this->knownPermissions as $known) {
                        if (@preg_match($pattern, $known)) {
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
                $prefix = substr($perm, 0, -2);
                foreach ($this->permissionTrie->getAllWithPrefix($prefix) as $known) {
                    $expanded[$known] = $value;
                }
            }
        }

        return $expanded;
    }
}
