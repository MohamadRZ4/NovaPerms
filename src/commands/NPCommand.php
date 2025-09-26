<?php

namespace MohamadRZ\NovaPerms\commands;

use MohamadRZ\CommandLib\BaseCommand;
use MohamadRZ\NovaPerms\commands\group\CreateGroup;
use MohamadRZ\NovaPerms\commands\group\DeleteGroup;
use MohamadRZ\NovaPerms\commands\group\GroupCommand;
use MohamadRZ\NovaPerms\commands\misc\HelpCommand;
use MohamadRZ\NovaPerms\commands\user\UserCommand;
use MohamadRZ\NovaPerms\model\Group;
use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\node\Types\RegexPermission;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\utils\Duration;
use MohamadRZ\NovaPerms\utils\ExecuteTimer;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

final class NPCommand extends BaseCommand
{
    public function __construct() {
        parent::__construct("novaperms", "Manage permissions");
        $this->setAliases(["np", "perm", "perms", "permission", "permissions"]);
        $this->setPermission("novaperms.use");
        $this->setPermissionMessage("§cYou don't have permission to us this command!");
    }


    /**
     * @return void
     */
    #[\Override] public function setup(): void
    {

        /*        $this->registerSubCommandAtPosition(0, new HelpCommand($this->plugin ,"help", "help command"));
        $this->registerSubCommandAtPosition(0, new CreateGroup($this->plugin ,"creategroup", "Create a new group"));
        $this->registerSubCommandAtPosition(0, new DeleteGroup($this->plugin ,"deletegroup", "Delete a group"));*/
        $this->addSubCommand(new UserCommand("user", "User management"));
        $this->addSubCommand(new HelpCommand("help", "help command"));
        $this->addSubCommand(new CreateGroup("creategroup", "Create a new group"));
        $this->addSubCommand(new DeleteGroup("deletegroup", "Delete a group"));
    }

    /**
     * @param CommandSender $sender
     * @param array $args
     * @param string $rootLabel
     * @return void
     */
    #[\Override] protected function onRun(CommandSender $sender, array $args, string $rootLabel): void
    {
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

/*                $time = new ExecuteTimer();
                $user = NovaPermsPlugin::getUserManager()->getUser($sender->getName());
                $regexNode = 'pocketmine.*';
                $user->addPermission(RegexPermission::builder($regexNode)->build());
                $time = $time->end();
                $sender->sendMessage("OK! (took {$time}ms)");*/
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
        return;
    }

}