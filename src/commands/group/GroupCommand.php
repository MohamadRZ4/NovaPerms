<?php

namespace MohamadRZ\NovaPerms\commands\group;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;

class GroupCommand extends BaseSubCommand
{

    /**
     * @return void
     */
    protected function prepare(): void
    {

    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    #[\Override] public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }
}