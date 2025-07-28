<?php

namespace MohamadRZ\NovaPerms\context\serializers;

use MohamadRZ\NovaPerms\context\BaseContextSet;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;

final class ContextDeserializer
{
    public static function deserialize(string $json): BaseContextSet
    {
        if (empty(trim($json)) || $json === '{}') {
            return ImmutableContextSet::empty();
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                return ImmutableContextSet::empty();
            }

            return self::arrayToContextSet($data);

        } catch (\JsonException $e) {
            return ImmutableContextSet::empty();
        }
    }

    public static function arrayToContextSet(array $data): ImmutableContextSet
    {
        if (empty($data)) {
            return ImmutableContextSet::empty();
        }

        $builder = ImmutableContextSet::builder();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (is_string($v) || is_numeric($v)) {
                        $builder->add((string)$key, (string)$v);
                    }
                }
            } elseif (is_string($value) || is_numeric($value)) {
                $builder->add((string)$key, (string)$value);
            }
        }

        return $builder->build();
    }

    public static function jsonToArray(string $json): array
    {
        if (empty(trim($json))) {
            return [];
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : [];
        } catch (\JsonException $e) {
            return [];
        }
    }
}
