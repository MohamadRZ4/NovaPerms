<?php

namespace MohamadRZ\NovaPerms\bulkupdate;

use MohamadRZ\NovaPerms\bulkupdate\action\BulkAction;

class UpdatePrimaryGroupAction implements BulkAction
{
    private string $groupName;

    private function __construct(string $groupName)
    {
        $this->groupName = $groupName;
    }

    public static function create(string $groupName): self
    {
        return new self($groupName);
    }

    public function getType(): string
    {
        return 'update_primary_group';
    }

    public function getData(): array
    {
        return ['group_name' => $this->groupName];
    }
}