<?php

namespace MohamadRZ\NovaPerms\commands\generic\parent;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;

class CommandParent extends BaseSubCommand
{

    /**
     * @return void
     */
    protected function prepare(): void
    {
        // TODO: Implement prepare() method.
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    #[\Override] public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        // TODO: Implement onRun() method.
    }
}