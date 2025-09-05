<?php

namespace MohamadRZ\NovaPerms\commands\misc;

use CortexPE\Commando\BaseSubCommand;
use MohamadRZ\NovaPerms\commands\NPCommand;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\CommandSender;

final class HelpCommand extends BaseSubCommand
{

    public function __construct(string $name, string $description = "", array $aliases = [])
    {
        $this->setPermission("novaperms.help");
        parent::__construct($name, $description, $aliases);
    }

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
        $plugin = NovaPermsPlugin::getInstance();
        $pluginName = $plugin->getDescription()->getName();
        $version = $plugin->getDescription()->getVersion();
        $available = [];
        foreach ($this->parent->getSubCommands() as $subCommand) {
            $id = spl_object_id($subCommand);
            if (!isset($available[$id]) && $subCommand->testPermissionSilent($sender)) {
                $available[$id] = $subCommand;
            }
        }
        $sender->sendMessage(NovaPermsPlugin::PREFIX . " §2Running §b{$pluginName} v{$version}§2.");
        $parentAliasUsed = $this->parent->getName();
        usort($available, function ($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });

        foreach ($available as $subCommand) {
            var_dump($subCommand->getArgumentList());
            $sender->sendMessage("§3> §a/$parentAliasUsed " . $subCommand->getName());
        }
    }
}