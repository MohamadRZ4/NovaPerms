<?php

namespace MohamadRZ\NovaPerms\model\primarygroup;

use MohamadRZ\NovaPerms\graph\PermissionCollector;
use MohamadRZ\NovaPerms\graph\ResolverConfig;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

class AllParentsByWeight extends Stored {
    public function calculateValue(): ?string {
        if ($this->value !== null) {
            return $this->value;
        }

        $groupManager = NovaPermsPlugin::getGroupManager();
        $userNodes = $this->user->getOwnNodes();

        $groupPermissionsMap = [];
        $groupInheritanceMap = [];

        foreach ($groupManager->getAllGroups() as $group) {
            $groupName = $group->getName();
            foreach ($group->getOwnNodes() as $node) {
                if ($node instanceof InheritanceNode) {
                    $groupInheritanceMap[$groupName][] = $node;
                } else {
                    $groupPermissionsMap[$groupName][] = $node;
                }
            }
        }

        $config = ResolverConfig::inheritanceOnly();

        $collector = new PermissionCollector(
            $userNodes,
            $groupPermissionsMap,
            $groupInheritanceMap,
            null,
            $config
        );

        $nodes = $collector->build();

        $lowestDepth = PHP_INT_MAX;
        $primaryGroup = null;

        foreach ($nodes as $node) {
            if ($node->depth < $lowestDepth) {
                $lowestDepth = $node->depth;
                $primaryGroup = $node->value;
            }
        }

        return $primaryGroup;
    }
}