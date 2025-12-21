<?php

namespace MohamadRZ\NovaPerms\model\primarygroup;

use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

class PrimaryGroupFactory {

    public static function create(User $user): AllParentsByWeight|ParentsByWeight|Stored
    {
        $mode = NovaPermsPlugin::getConfigManager()->getPrimaryGroupCalculation();

        return match ($mode) {
            'parents-by-weight' => new ParentsByWeight($user),
            'all-parents-by-weight' => new AllParentsByWeight($user),
            default => new Stored($user),
        };
    }
}
