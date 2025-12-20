<?php

namespace MohamadRZ\NovaPerms\node\serialize;

use MohamadRZ\NovaPerms\context\serialize\ContextDeserializer;
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

        foreach ($rawData as $permission) {

            if (is_string($permission)) {
                $permission = [
                    "name" => trim($permission)
                ];
            }

            if (!is_array($permission)) {
                $permission = [];
            }

            $name = trim((string)($permission["name"] ?? ""));

            if ($name === "") {
                return [];
            }

            $value   = $permission["value"] ?? true;
            $expire  = $permission["expire"] ?? null;

            $parts = explode(".", $name);
            $node  = null;
            $type  = strtolower($parts[0] ?? "");

            switch ($type) {
                case "prefix":
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
                case "display":
                    if (count($parts) >= 2) {
                        $node = DisplayNameNode::builder($parts[1]);
                    }
                    break;

                case "group":
                    if (count($parts) >= 2) {
                        $node = InheritanceNode::builder($parts[1]);
                    }
                    break;

                case "weight":
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

            if ($node === null) {
                $node = PermissionNode::builder($name);
            }

            $node->value((bool)$value);

            if ($expire !== null) {
                $node->expiry((int)$expire);
            }

            $result[] = $node->build();
        }

        return $result;
    }

}
