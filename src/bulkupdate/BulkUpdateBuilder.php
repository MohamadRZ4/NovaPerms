<?php

namespace MohamadRZ\NovaPerms\bulkupdate;

use MohamadRZ\NovaPerms\bulkupdate\action\BulkAction;

class BulkUpdateBuilder
{
    private BulkUpdate $operation;

    private function __construct()
    {
        $this->operation = new BulkUpdate();
    }

    public static function create(): self
    {
        return new self();
    }

    public function dataType(DataType $dataType): self
    {
        $this->operation->setDataType($dataType);
        return $this;
    }

    public function trackStatistics(bool $track): self
    {
        $this->operation->setTrackStatistics($track);
        return $this;
    }

    public function action(BulkAction $action): self
    {
        $this->operation->setAction($action);
        return $this;
    }

    public function filter(BulkUpdateField $field, Comparison $comparison, mixed $value): self
    {
        $this->operation->addFilter(new FilterConstraint($field, $comparison, $value));
        return $this;
    }

    public function build(): BulkUpdate
    {
        if ($this->operation->getAction() === null) {
            throw new \InvalidArgumentException("Action must be set");
        }
        return $this->operation;
    }
}