<?php

namespace MohamadRZ\NovaPerms\model;


abstract class PermissionHolder
{
    protected PermissionManager $permissionManager;
    protected string $holderId;
    protected string $holderType;

    public function __construct()
    {
        $this->permissionManager = new PermissionManager();
    }

    public function addPermission(string $permission): void
    {
        $this->permissionManager->addPermission($this->holderType, $this->holderId, $permission);
    }

    public function removePermission(string $permission): void
    {
        $this->permissionManager->removePermission($this->holderType, $this->holderId, $permission);
    }

    public function getPermissions(): array
    {
        return $this->permissionManager->getPermissions($this->holderType, $this->holderId);
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissionManager->setPermissions($this->holderType, $this->holderId, $permissions);
    }

    public function clearPermissions(): void
    {
        $this->permissionManager->clearPermissions($this->holderType, $this->holderId);
    }

    public function getPermissionCount(): int
    {
        return $this->permissionManager->getPermissionCount($this->holderType, $this->holderId);
    }

    public function copyPermissionsFrom(PermissionHolder $other): void
    {
        $permissions = $other->getPermissions();
        $this->setPermissions($permissions);
    }

    abstract protected function setHolderId(string $id): void;
    abstract protected function setHolderType(string $type): void;
}
