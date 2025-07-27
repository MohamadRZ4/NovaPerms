<?php

namespace MohamadRZ\NovaPerms\verbose\filter;

use MohamadRZ\NovaPerms\verbose\data\VerboseEntry;

final class PermissionFilter extends VerboseFilter {

    private string $permission;

    public function __construct(string $permission) {
        $this->permission = strtolower($permission);
    }

    public function matches(VerboseEntry $entry): bool {
        return str_starts_with(strtolower($entry->getPermission()), $this->permission);
    }
}
