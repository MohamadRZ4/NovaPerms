<?php

namespace MohamadRZ\NovaPerms\node\serialize;

use MohamadRZ\NovaPerms\context\serialize\ContextSerializer;
use MohamadRZ\NovaPerms\node\Node;

final class NodeSerializer
{
    /**
     * @param Node[] $nodes
     * @return array
     */
    public static function serialize(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            $entry = [
                "name"    => $node->getKey(),
                "value"   => $node->getValue(),
                "expire"  => $node->getExpiry()
            ];

            // coming soon: context
            /* $context = $node->getContext();
            if (!$context->isEmpty()) {
                $entry["context"] = ContextSerializer::serialize($context);
            } */

            $result[] = $entry;
        }

        return $result;
    }
}

