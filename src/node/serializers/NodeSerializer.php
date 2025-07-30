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
                "context"=> ContextSerializer::serialize($node->getContext()),
            ];
            $result[] = $entry;
        }

        return $result;
    }
}
