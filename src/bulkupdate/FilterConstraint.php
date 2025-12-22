<?php

namespace MohamadRZ\NovaPerms\bulkupdate;

class FilterConstraint
{
    public function __construct(
        public BulkUpdateField $field,
        public Comparison $comparison,
        public mixed $value
    ) {}
}