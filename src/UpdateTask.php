<?php

namespace MohamadRZ\NovaPerms;

use MohamadRZ\NovaPerms\model\PermissionHolder;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\Node;
use MohamadRZ\NovaPerms\utils\Duration;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;

class UpdateTask extends Task
{
    public function onRun(): void
    {
        $userManager = NovaPermsPlugin::getUserManager();
        $groupManager = NovaPermsPlugin::getGroupManager();

        foreach ($userManager->getAllUsers() as $user) {
            $user->auditTemporaryNodes();
        }

        foreach ($groupManager->getAllGroups() as $group) {
            $group->auditTemporaryNodes();
        }
    }
}
