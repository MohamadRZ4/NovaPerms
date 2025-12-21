<?php

namespace MohamadRZ\NovaPerms\model\primarygroup;

use MohamadRZ\NovaPerms\model\User;

class Stored implements PrimaryGroupHolder {
    protected User $user;
    protected ?string $value = null;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function calculateValue(): ?string {
        return $this->value;
    }

    public function getStoredValue(): ?string {
        return $this->value;
    }

    public function setStoredValue(?string $value): void {
        $this->value = ($value === null || $value === '') ? null : strtolower($value);
    }
}