<?php

namespace MohamadRZ\NovaPerms\command;

use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\model\PermissionHolder;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\lang\Translatable;

class NovaPermsCommand extends Command
{
    public const PREFIX = "§7[§l§bN§3P§4§r§7]§r";

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = [])
    {
        $this->setAliases(["np", "perms", "permission", "permissions"]);
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        $sub = array_shift($args) ?? null;

        if ($sub !== null) {
            switch ($sub) {
                case "creategroup":
                    $this->createGroupHandler($sender, $args);
                    break;
                case "deletegroup":
                    $this->deleteGroupHandler($sender, $args);
                case "user":
                    $this->userHandler($sender, $args);
                    break;
            }
        } else {
            $groups = NovaPermsPlugin::getGroupManager()->getAllGroups();

            if (count($groups) <= 1) {
                $onlyGroup = reset($groups);
                if (
                    $onlyGroup->getName() === GroupManager::DEFAULT_GROUP &&
                    count($onlyGroup->getOwnPermissionNodes()) === 0
                ) {
                    $pluginName = NovaPermsPlugin::getInstance()->getDescription()->getName();
                    $version = NovaPermsPlugin::getInstance()->getDescription()->getVersion();
                    $sender->sendMessage(self::PREFIX." §2Running §b".$pluginName." v".$version."§2.");
                    $sender->sendMessage(self::PREFIX." §3Its seems that no permissions have been setup yet!");
                    $sender->sendMessage(self::PREFIX." §3Befor yoy can use any of the $pluginName commands in-game, you need to use the console to give yourself access.");
                    $sender->sendMessage(self::PREFIX." §3Open your console and run:");
                    $sender->sendMessage(self::PREFIX."  §3§l»§r §anp user {$sender->getName()} permission set novaperms.* true");
                    $sender->sendMessage(" ");
                    $sender->sendMessage(self::PREFIX." §3After you've done this, you can begin to define your permission assignments and groups.");
                    $sender->sendMessage(self::PREFIX." §3Don't know where to start? Check here:");
                    $sender->sendMessage(" §7https://github.com/MohamadRZ4/NovaPerms/wiki");
                } else {
                    $sender->sendMessage("you dont have permission.");
                }
            }
        }
    }

    public function createGroupHandler(CommandSender $sender, array $args): void
    {
        $name = array_shift($args) ?? null;
        $weight = array_shift($args) ?? null;
        $displayName = array_shift($args) ?? "";

        if ($name === null) {
            $sender->sendMessage("enter Name");
            return;
        }

        if (NovaPermsPlugin::getGroupManager()->createGroup($name, $weight, $displayName)) {
            $sender->sendMessage("group $name created.");
        } else {
            $sender->sendMessage("group exist.");
        }
    }

    public function deleteGroupHandler(CommandSender $sender, array $args): void
    {
        $name = array_shift($args) ?? null;
        if ($name === null) {
            $sender->sendMessage("enter Name");
            return;
        }
        NovaPermsPlugin::getGroupManager()->deleteGroup($name);
        $sender->sendMessage("successfully \"$name\" deleting.");
    }

    public function userHandler(CommandSender $sender, array $args): void
    {
        $target = array_shift($args) ?? null;
        $sub = array_shift($args) ?? null;

        if ($sub === null || $target === null) {
            $sender->sendMessage("error 1");
        }

        $user = NovaPermsPlugin::getUserManager()->getUser($target);
        if ($user)

        switch (strtolower($sub)) {
            case "permission":
                break;
        }
    }

    public function permissionHandler(CommandSender $sender, array $args, PermissionHolder $holder): void
    {

    }
}