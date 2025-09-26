<?php

namespace MohamadRZ\NovaPerms\commands\user;

use MohamadRZ\CommandLib\BaseCommand;
use MohamadRZ\CommandLib\PlayerArgument;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;

class UserCommand extends BaseCommand
{

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = [])
    {
        $this->setPermission("novaperms.use");
        parent::__construct($name, $description, $usageMessage, $aliases);
    }
    /**
     * @return void
     */
    #[\Override] public function setup(): void
    {
        $this->setArgumentsBeforeSubcommands();
        $this->addArgument(new PlayerArgument("name", false));
        $this->addSubCommand(new UserInfo("info"));
    }

    /**
     * @param CommandSender $sender
     * @param array $args
     * @param string $rootLabel
     * @return void
     */
    #[\Override] protected function onRun(CommandSender $sender, array $args, string $rootLabel): void
    {
        $usage = NovaPermsPlugin::PREFIX . "§b".ucfirst($this->getName())." Sub Commands: §7" . "(/np {$this->getName()} <user> ...)" . "\n";
        foreach ($this->getSubCommands() as $subCommand) {
            $usage .= "§3> §a{$subCommand->getName()}" . "\n";
        }
        $sender->sendMessage($usage);
    }
}
