<?php

namespace MohamadRZ\NovaPerms;

use MohamadRZ\NovaPerms\model\PermissionHolder;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\utils\Duration;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;

class UpdateTask extends Task
{
    #[\Override]
    public function onRun(): void
    {
        $userManager = NovaPermsPlugin::getUserManager();
        $groupManager = NovaPermsPlugin::getGroupManager();

        foreach ($userManager->getAllUsers() as $user) {
            $this->checkHolderNodes($user);
        }

        foreach ($groupManager->getAllGroups() as $group) {
            $this->checkHolderNodes($group);
        }
    }

    /**
     * @param PermissionHolder $holder
     */
    private function checkHolderNodes(PermissionHolder $holder): void
    {
        $nodes = $holder->getOwnPermissionNodes();
        foreach ($nodes as $node) {
            if ($this->isNodeExpired($node)) {
                $holder->removePermission($node);
                NovaPermsPlugin::getInstance()->getLogger()->debug("Removed expired node '{$node->getKey()}' from {$holder->getName()}");
            }
        }
    }

    private function isNodeExpired(AbstractNode $node): bool
    {
        if ($node->getExpiry() === -1) {
            return false;
        }

        $duration = new Duration($node->getExpiry());
        $expiry = $duration->getExpiryTimestamp(time());

        return time() >= $expiry;
    }
}
