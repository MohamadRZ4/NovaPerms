<?php

namespace MohamadRZ\StellarRanks\verbose\filter;

use MohamadRZ\StellarRanks\verbose\data\VerboseEntry;

final class PermissionFilter extends VerboseFilter {

    private string $permission;

    public function __construct(string $permission) {
        $this->permission = strtolower($permission);
    }

    public function matches(VerboseEntry $entry): bool {
        return str_starts_with(strtolower($entry->getPermission()), $this->permission);
    }
}
