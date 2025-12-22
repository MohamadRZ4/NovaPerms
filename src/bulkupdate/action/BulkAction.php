<?php

namespace MohamadRZ\NovaPerms\bulkupdate\action;

interface BulkAction
{
    public function getType(): string;
    public function getData(): array;
}