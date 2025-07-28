<?php

namespace MohamadRZ\NovaPerms\context\serializers;

use MohamadRZ\NovaPerms\context\BaseContextSet;

final class ContextSerializer
{
    public static function serialize(BaseContextSet $contexts): string
    {
        if ($contexts->isEmpty()) {
            return '{}';
        }

        $data = [];
        $contextMap = $contexts->toMap();

        foreach ($contextMap as $key => $values) {
            if (count($values) === 1) {
                $data[$key] = $values[0];
            } else {
                $data[$key] = array_values($values);
            }
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function serializeArray(array $contextData): string
    {
        return json_encode($contextData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}