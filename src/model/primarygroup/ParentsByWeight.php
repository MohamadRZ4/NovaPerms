<?php

namespace MohamadRZ\NovaPerms\model\primarygroup;

use MohamadRZ\NovaPerms\graph\PermissionCollector;
use MohamadRZ\NovaPerms\graph\ResolverConfig;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

class ParentsByWeight extends Stored {
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

        $config = ResolverConfig::inheritanceOnly()->withMaxDepth(0);

        $collector = new PermissionCollector(
            $userNodes,
            $groupPermissionsMap,
            $groupInheritanceMap,
            null,
            $config
        );

        $nodes = $collector->build();

        foreach ($nodes as $node) {
            if ($node->depth === 0) {
                return $node->value;
            }
        }

        return null;
    }
}