<?php

namespace MohamadRZ\StellarRanks\verbose\filter;

use MohamadRZ\StellarRanks\verbose\data\VerboseEntry;

abstract class VerboseFilter {

    abstract public function matches(VerboseEntry $entry): bool;

    public static function parse(string $expression): VerboseFilter {
        $parser = new FilterParser();
        return $parser->parse($expression);
    }

    public static function createPlayerFilter(string $playerName): VerboseFilter {
        return new PlayerFilter($playerName);
    }

    public static function createPermissionFilter(string $permission): VerboseFilter {
        return new PermissionFilter($permission);
    }

    public static function createCombinedFilter(VerboseFilter $left, FilterOperator $operator, VerboseFilter $right): VerboseFilter {
        return new CombinedFilter($left, $operator, $right);
    }
}
