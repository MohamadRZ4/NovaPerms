<?php

namespace MohamadRZ\NovaPerms\commands\misc;

use MohamadRZ\CommandLib\BaseCommand;
use MohamadRZ\NovaPerms\commands\group\GroupCommand;
use MohamadRZ\NovaPerms\commands\NPCommand;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\plugin\PluginBase;

final class HelpCommand extends BaseCommand
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
        // TODO: Implement setup() method.
    }

    protected function onRun(CommandSender $sender, array $args, string $rootLabel): void
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
        usort($available, function ($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });

        foreach ($available as $subCommand) {
            $sender->sendMessage("§3> §a/$rootLabel " . $subCommand->getName());
        }
    }
}