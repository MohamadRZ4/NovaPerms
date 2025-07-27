<?php

namespace MohamadRZ\NovaPerms\model;

class User extends PermissionHolder
{
    private string $primaryKey;

    public function __construct(string $primaryKey)
    {
        parent::__construct();
        $this->holderId = $primaryKey;
        $this->setHolderType('user');
    }

    protected function setHolderId(string $id): void
    {
        $this->holderId = $id;
    }

    protected function setHolderType(string $type): void
    {
        $this->holderType = $type;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function importData(array $data): void
    {

    }
}
