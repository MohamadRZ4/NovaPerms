<?php

namespace MohamadRZ\NovaPerms\node\serializers;

use JsonException;
use MohamadRZ\NovaPerms\context\serializers\ContextSerializer;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\Types\DisplayName;
use MohamadRZ\NovaPerms\node\Types\Inheritance;
use MohamadRZ\NovaPerms\node\Types\Meta;
use MohamadRZ\NovaPerms\node\Types\Permission;
use MohamadRZ\NovaPerms\node\Types\Prefix;
use MohamadRZ\NovaPerms\node\Types\Suffix;
use MohamadRZ\NovaPerms\node\Types\Weight;

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
                "context"=> ContextSerializer::serialize($node->getContext()) ?? [],
            ];
            $result[] = $entry;
        }

        return $result;
    }
}
