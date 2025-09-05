<?php

namespace MohamadRZ\NovaPerms\commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use MohamadRZ\NovaPerms\commands\misc\HelpCommand;
use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;

final class NPCommand extends BaseCommand
{

    public function __construct(NovaPermsPlugin $plugin) {
        parent::__construct($plugin, "novaperms", "Manage permissions");
        $this->setAliases(["np", "perm", "perms", "permission", "permissions"]);
        $this->setPermission("novaperms.use");
        $this->setPermissionMessage("§cYou don't have permission to us this command!");
    }

    /**
     * @return void
     */
    protected function prepare(): void
    {
        $this->registerSubCommand(new HelpCommand("help", "help command"));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    #[\Override]
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        $groups = NovaPermsPlugin::getGroupManager()->getAllGroups();
        $plugin = NovaPermsPlugin::getInstance();
        $pluginName = $plugin->getDescription()->getName();
        $version = $plugin->getDescription()->getVersion();

        $noPermission = function() use ($sender) {
            $sender->sendMessage("§cYou don't have permission to use this command!");
        };

        if (count($groups) <= 1) {
            /** @var Group $onlyGroup */
            $onlyGroup = reset($groups);

            $isDefaultNoPerms = (
                $onlyGroup->getName() === GroupManager::DEFAULT_GROUP &&
                count($onlyGroup->getOwnPermissionNodes()) === 0 &&
                !$sender->hasPermission("novaperms.help")
            );

            if ($isDefaultNoPerms) {
                $sender->sendMessage(NovaPermsPlugin::PREFIX . " §2Running §b{$pluginName} v{$version}§2.");
                $sender->sendMessage(NovaPermsPlugin::PREFIX . " §3It seems that no permissions have been set up yet!");
                $sender->sendMessage(NovaPermsPlugin::PREFIX . " §3Before you can use any of the {$pluginName} commands in-game, you need to give yourself access from the console.");
                $sender->sendMessage(NovaPermsPlugin::PREFIX . " §3Open your console and run:");
                $sender->sendMessage(NovaPermsPlugin::PREFIX . "  §3§l»§r §anp user {$sender->getName()} permission set novaperms.* true");
                $sender->sendMessage(" ");
                $sender->sendMessage(NovaPermsPlugin::PREFIX . " §3After this, you can begin defining permission assignments and groups.");
                $sender->sendMessage(NovaPermsPlugin::PREFIX . " §3Need help? Check here:");
                $sender->sendMessage(" §7https://github.com/MohamadRZ4/NovaPerms/wiki");
                return;
            }

            if (!$sender->hasPermission("novaperms.help")) {
                $noPermission();
                return;
            }

            $sender->sendMessage(NovaPermsPlugin::PREFIX . " §2Running §b{$pluginName} v{$version}§2.");
            $sender->sendMessage(NovaPermsPlugin::PREFIX . " §2Use §a/np help §3to view available commands.");
            return;
        }

        if (!$sender->hasPermission("novaperms.help")) {
            $noPermission();
            return;
        }

        $sender->sendMessage(NovaPermsPlugin::PREFIX . " §2Running §b{$pluginName} v{$version}§2.");
        $sender->sendMessage(NovaPermsPlugin::PREFIX . " §2Use §a/np help §3to view available commands.");
    }
}