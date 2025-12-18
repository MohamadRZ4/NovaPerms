<?php

namespace MohamadRZ\NovaPerms\context\serialize;

use MohamadRZ\NovaPerms\context\ContextSet;

class ContextDeserializer {

    public static function deserialize(string|array|null $input): ContextSet {
        $context = new ContextSet();

        if ($input === null) {
            return $context;
        }

        if (is_array($input) && self::isAssoc($input)) {
            foreach ($input as $key => $value) {
                $context->add((string)$key, (string)$value);
            }
            return $context;
        }

        if (is_array($input)) {
            $input = implode(" ", $input);
        }

        $normalized = strtolower(trim($input));

        $normalized = str_replace(
            [",", "_"],
            " ",
            $normalized
        );

        $normalized = preg_replace('/\s+/', ' ', $normalized);

        $pairs = explode(" ", $normalized);

        foreach ($pairs as $pair) {
            if (!str_contains($pair, "=")) continue;

            [$key, $value] = explode("=", $pair, 2);

            if ($key === "" || $value === "") continue;

            $context->add($key, $value);
        }

        return $context;
    }

    private static function isAssoc(array $array): bool {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
