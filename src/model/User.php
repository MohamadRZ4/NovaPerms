<?php

namespace MohamadRZ\NovaPerms\model;

class User extends PermissionHolder
{
    private string $primaryKey;
    private $xuid;
    private $username;

    public function __construct(string $primaryKey)
    {
        parent::__construct();
        $this->holderId = $primaryKey;
        $this->setHolderType('user');
    }

    public function getXuid(): string
    {
        return $this->xuid;
    }

    /**
     * @param mixed $xuid
     */
    public function setXuid($xuid): void
    {
        $this->xuid = $xuid;
    }

    public function getUsername():string
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
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

    public function importNodes(array $data): void
    {
        
    }
}
