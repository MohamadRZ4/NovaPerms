<?php

namespace MohamadRZ\NovaPerms\bulkupdate\action;

class UpdateAction implements BulkAction
{
    private ?string $permission = null;
    private ?bool $value = null;
    private ?int $expiry = null;
    private bool $isUpsert = false;

    public static function create(): self { return new self(); }

    public function setPermission(string $permission): self
    {
        $this->permission = $permission;
        $this->isUpsert = true;
        return $this;
    }

    public function setValue(bool $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function setExpiry(int $expiry): self
    {
        $this->expiry = $expiry;
        return $this;
    }

    public function getType(): string
    {
        return $this->isUpsert ? 'upsert' : 'update';
    }

    public function getData(): array
    {
        return [
            'permission' => $this->permission,
            'value' => $this->value,
            'expiry' => $this->expiry
        ];
    }
}