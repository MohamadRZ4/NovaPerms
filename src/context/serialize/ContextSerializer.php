<?php

namespace MohamadRZ\NovaPerms\context\serialize;

use MohamadRZ\NovaPerms\context\ContextSet;

class ContextSerializer {

    public static function serialize(ContextSet $context): string {
        $parts = [];

        foreach ($context->all() as $key => $value) {
            $parts[] = "{$key}={$value}";
        }

        return implode(", ", $parts);
    }
}
