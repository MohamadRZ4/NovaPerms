<?php

namespace MohamadRZ\NovaPerms\node\serializers;

use JsonException;
use MohamadRZ\NovaPerms\context\serializers\ContextSerializer;
use MohamadRZ\NovaPerms\node\AbstractNode;

final class NodeSerializer
{
    /**
     * @param AbstractNode[] $nodes
     * @return array
     */
    public static function serialize(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            /** @var AbstractNode $node */
            $entry = [
                "name"   => $node->getKey(),
                "value"  => $node->getValue(),
                "expire" => $node->getExpiry(),
                /*"context"=> ContextSerializer::serialize($node->getContext()),*/
            ];
            $result[] = $entry;
        }

        return $result;
    }

    public static function isSerializedNode(mixed $data): bool {
        if (
            is_array($data) &&
            array_key_exists("name", $data) &&
            array_key_exists("value", $data) &&
            array_key_exists("expire", $data) &&
            is_string($data["name"]) &&
            is_bool($data["value"]) &&
            (is_int($data["expire"]) || $data["expire"] === null)
        ) {
            return true;
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                if (!self::isSerializedNode($item)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

}
