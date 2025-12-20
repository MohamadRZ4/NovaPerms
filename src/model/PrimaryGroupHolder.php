<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\model\Group;

interface PrimaryGroupHolder {

    /**
     * Gets the name of the primary group, or null.
     *
     * @return string|null
     */
    public function calculateValue(): ?string;

    /**
     * Gets the stored primary group.
     *
     * @return string|null
     */
    public function getStoredValue(): ?string;

    /**
     * Sets the stored primary group.
     *
     * @param string|null $value
     */
    public function setStoredValue(?string $value): void;
}

class Stored implements PrimaryGroupHolder {
    protected User $user;
    protected ?string $value = null;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function calculateValue() : ?string{
        // TODO: Implement calculateValue() method.
    }

    public function getStoredValue(): ?string {
        return $this->value;
    }

    public function setStoredValue(?string $value): void {
        $this->value = ($value === null || $value === '') ? null : strtolower($value);
    }
}

class AllParentsByWeight extends Stored {
    public function calculateValue() : ?string{
        // TODO: Implement calculateValue() method.
    }
}

/**
 * Returns the parent group with highest weight.
 */
class ParentsByWeight extends Stored {
    public function calculateValue() : ?string{
        // TODO: Implement calculateValue() method.
    }
}
