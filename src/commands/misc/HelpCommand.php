<?php

namespace MohamadRZ\NovaPerms\commands\misc;

use CortexPE\Commando\BaseSubCommand;
use MohamadRZ\NovaPerms\commands\group\GroupCommand;
use MohamadRZ\NovaPerms\commands\NPCommand;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

final class HelpCommand extends BaseSubCommand
{

    public function __construct(PluginBase $plugin, string $name, string $description = "", array $aliases = [])
    {
        $this->setPermission("novaperms.help");
        parent::__construct($plugin, $name, $description, $aliases);
    }

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
        $plugin = NovaPermsPlugin::getInstance();
        $pluginName = $plugin->getDescription()->getName();
        $version = $plugin->getDescription()->getVersion();
        $sender->sendMessage(NovaPermsPlugin::PREFIX . " §2Running §b{$pluginName} v{$version}§2.");
        $available = [];
        foreach ($this->parent->getSubCommands() as $subCommand) {
            $id = spl_object_id($subCommand);
            if (!isset($available[$id]) && $subCommand->testPermissionSilent($sender)) {
                $available[$id] = $subCommand;
            }
        }
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