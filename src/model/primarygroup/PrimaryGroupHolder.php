<?php

namespace MohamadRZ\NovaPerms\model\primarygroup;

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