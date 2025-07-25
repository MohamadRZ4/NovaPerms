<?php

namespace MohamadRZ\StellarRanks\verbose\filter;

use MohamadRZ\StellarRanks\verbose\data\VerboseEntry;

final class CombinedFilter extends VerboseFilter {

    private VerboseFilter $left;
    private FilterOperator $operator;
    private VerboseFilter $right;

    public function __construct(VerboseFilter $left, FilterOperator $operator, VerboseFilter $right) {
        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
    }

    public function matches(VerboseEntry $entry): bool {
        return match($this->operator) {
            FilterOperator::AND => $this->left->matches($entry) && $this->right->matches($entry),
            FilterOperator::OR => $this->left->matches($entry) || $this->right->matches($entry),
            FilterOperator::NOT => !$this->left->matches($entry)
        };
    }
}
