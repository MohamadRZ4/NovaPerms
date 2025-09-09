<?php

namespace MohamadRZ\NovaPerms\commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use MohamadRZ\NovaPerms\commands\group\CreateGroup;
use MohamadRZ\NovaPerms\commands\group\DeleteGroup;
use MohamadRZ\NovaPerms\commands\group\GroupCommand;
use MohamadRZ\NovaPerms\commands\misc\HelpCommand;
use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;

final class NPCommand extends BaseCommand
{

    public Plugin $plugin;

    public function __construct(NovaPermsPlugin $plugin) {
        parent::__construct($plugin, "novaperms", "Manage permissions");
        $this->plugin = $plugin;
        $this->setAliases(["np", "perm", "perms", "permission", "permissions"]);
        $this->setPermission("novaperms.use");
        $this->setPermissionMessage("§cYou don't have permission to us this command!");
    }

    /**
     * @return void
     */
    protected function prepare(): void
    {
        $this->registerSubCommand(new HelpCommand($this->plugin ,"help", "help command"));
        $this->registerSubCommand(new CreateGroup($this->plugin ,"creategroup", "Create a new group"));
        $this->registerSubCommand(new DeleteGroup($this->plugin ,"deletegroup", "Delete a group"));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    #[\Override]
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        $noPermission = function() use ($sender) {
            $sender->sendMessage("§cYou don't have permission to use this command!");
        };

        $groups = NovaPermsPlugin::getGroupManager()->getAllGroups();
        $plugin = NovaPermsPlugin::getInstance();
        $pluginName = $plugin->getDescription()->getName();
        $version = $plugin->getDescription()->getVersion();

        if (count($groups) <= 1) {
            /** @var Group|null $onlyGroup */
            $onlyGroup = reset($groups);

            if ($onlyGroup instanceof Group) {
                $isDefaultNoPerms = (
                    $onlyGroup->getName() === GroupManager::DEFAULT_GROUP &&
                    count($onlyGroup->getOwnPermissionNodes()) === 0 &&
                    !$sender->hasPermission("novaperms.help")
                );
            } else {
                $isDefaultNoPerms = false;
            }

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

        $sender->sendMessage(NovaPermsPlugin::PREFIX . " §2Running §b{$pluginName} v{$version}§2.");
        $sender->sendMessage(NovaPermsPlugin::PREFIX . " §2Use §a/np help §3to view available commands.");
    }
}