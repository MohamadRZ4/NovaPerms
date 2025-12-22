<?php

namespace MohamadRZ\NovaPerms\bulkupdate\action;

class DeleteAction implements BulkAction
{
    public static function create(): self
    {
        return new self();
    }

    public function getType(): string
    {
        return 'delete';
    }

    public function getData(): array
    {
        return [];
    }
}