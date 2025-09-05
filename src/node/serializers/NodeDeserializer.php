<?php

namespace MohamadRZ\NovaPerms\node\serializers;

use MohamadRZ\NovaPerms\context\ImmutableContextSet;
use MohamadRZ\NovaPerms\context\serializers\ContextDeserializer;
use MohamadRZ\NovaPerms\node\Types\DisplayNameNode;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\node\Types\MetaNode;
use MohamadRZ\NovaPerms\node\Types\PermissionNode;
use MohamadRZ\NovaPerms\node\Types\PrefixNode;
use MohamadRZ\NovaPerms\node\Types\SuffixNode;
use MohamadRZ\NovaPerms\node\Types\WeightNode;

final class NodeDeserializer
{
    public static function deserialize(array $rawData): array
    {
        $result = [];

        if (empty($rawData)) {
            return $result;
        }

        foreach ($rawData as $permission) {
            $name = trim($permission["name"] ?? "");
            if ($name === "") {
                continue;
            }

            $value   = $permission["value"]  ?? true;
            $expire  = $permission["expire"] ?? null;
            $context = $permission["context"] ?? [];

            $parts = explode(".", $name);
            $type  = strtolower($parts[0] ?? "");
            $node  = null;

            switch ($type) {
                case "perfix":
                    if (count($parts) >= 3) {
                        $node = PrefixNode::builder($parts[2], $parts[1]);
                    }
                    break;

                case "suffix":
                    if (count($parts) >= 3) {
                        $node = SuffixNode::builder($parts[2], $parts[1]);
                    }
                    break;

                case "displayname":
                    if (count($parts) >= 2) {
                        $node = DisplayNameNode::builder($parts[1]);
                    }
                    break;

                case "group":
                    if (count($parts) >= 2) {
                        $node = InheritanceNode::builder($parts[1]);
                    }
                    break;

                case "wight":
                    if (count($parts) >= 2) {
                        $node = WeightNode::builder($parts[1]);
                    }
                    break;

                case "meta":
                    if (count($parts) >= 3) {
                        $node = MetaNode::builder($parts[1], $parts[2]);
                    }
                    break;
            }

            if (!$node) {
                $node = PermissionNode::builder($name);
            }

            $node->value($value);
            if ($expire !== null) {
                $node->expiry((int)$expire);
            }

            //$node->withContext($context);

            $result[] = $node->build();
        }

        return $result;
    }

}