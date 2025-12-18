<?php

namespace MohamadRZ\NovaPerms\command;

use MohamadRZ\NovaPerms\model\GroupManager;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\serialize\NodeDeserializer;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\utils\Duration;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;

class NovaPermsCommand extends Command
{
    public const PREFIX = "§7[§l§bN§3P§4§r§7]§r";

    public function __construct(string $name)
    {
        $this->setAliases(["np", "perms", "permission", "permissions"]);
        $this->setPermission("novaperms.use");
        parent::__construct($name);
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
        NovaPermsPlugin::getGroupManager()->deleteGroup($name)->onCompletion(
            function () use ($sender, $name) {
                $sender->sendMessage("successfully \"$name\" deleting.");
            },
            function () use ($sender, $name) {
                $sender->sendMessage("Error group \"$name\" not found or not loaded");
            }
        );
    }

    public function userHandler(CommandSender $sender, array $args): void
    {
        $target = array_shift($args) ?? null;
        $sub = array_shift($args) ?? null;

        if ($sub === null || $target === null) {
            $sender->sendMessage("error 1");
        }

        switch (strtolower($sub)) {
            case "permission":
                $this->permissionForUsersHandler($sender, $args, $target);
                break;
        }
    }

    public function groupHandler(CommandSender $sender, array $args): void
    {
        $group = array_shift($args) ?? null;
        $sub = array_shift($args) ?? null;

        if ($sub === null || $group === null) {
            $sender->sendMessage("error 2");
        }

        switch (strtolower($sub)) {
            case "permission":
                $this->permissionForGroupHandler($sender, $args, $group);
                break;
        }
    }

    public function permissionForUsersHandler(CommandSender $sender, array $args, string $target): void
    {
        $sub = array_shift($args) ?? null;

        if ($sub === null) {
            $sender->sendMessage("error 3");
        }

        switch (strtolower($sub)) {
            case "info":
                NovaPermsPlugin::getUserManager()->modifyUser($target, function (User $user) use ($sender) {
                    $permissions = $user->getPermissions();

                    $sender->sendMessage(TF::GOLD . str_repeat("-", 30));
                    $sender->sendMessage(TF::YELLOW . " NovaPerms " . TF::GRAY . "User Info {$user->getName()
                }");
                    $sender->sendMessage(TF::GOLD . str_repeat("-", 30));

                    if (empty($permissions)) {
                        $sender->sendMessage(TF::GRAY . "player have no permissions assigned.");
                    } else {
                        foreach ($permissions as $key => $node) {
                            $expiry = ($node->getExpiry() === -1)
                                ? TF::GREEN . "never"
                                : TF::RED . date("Y-m-d H:i:s", $node->getExpiry()) . TF::GRAY
                                . " (" . Duration::betweenNowAnd($node->getExpiry())->format() . " left)";

                            $sender->sendMessage(
                                TF::AQUA . " - " . TF::WHITE . $key .
                                TF::DARK_GRAY . " | " . TF::GREEN . ($node->getValue() ? "true" : "false") .
                                TF::DARK_GRAY . " | " . TF::GRAY . "expires: " . $expiry
                            );
                        }
                    }

                    $sender->sendMessage(TF::GOLD . str_repeat("-", 30));
                });
                break;
            case "set":
                NovaPermsPlugin::getUserManager()->modifyUser($target, function (User $user) use ($sender, $args) {

                    $nodeString = array_shift($args);
                    if ($nodeString === null) {
                        $sender->sendMessage("error 4");
                        return;
                    }

                    $value = true;

                    if (!empty($args) && in_array(strtolower($args[0]), ['true', 'false'], true)) {
                        $value = strtolower(array_shift($args)) === 'true';
                    }

                    $user->addPermission($nodeString, $value);

                    $sender->sendMessage("Permission '{$nodeString}' set to " . ($value ? "true" : "false"));
                });
                break;
            case "unset":
                NovaPermsPlugin::getUserManager()->modifyUser($target, function (User $user) use ($sender, $args) {

                    $nodeString = array_shift($args);
                    if ($nodeString === null) {
                        $sender->sendMessage("error: No node specified.");
                        return;
                    }

                    if ($user->removePermission($nodeString)) {
                        $sender->sendMessage("Permission '{$nodeString}' unset successfully.");
                    } else {
                        $sender->sendMessage("Permission '{$nodeString}' not found or couldn't be removed.");
                    }
                });
                break;
            case "settemp":
                NovaPermsPlugin::getUserManager()->modifyUser($target, function (User $user) use ($sender, $args) {

                    $nodeString = array_shift($args);
                    if ($nodeString === null) {
                        $sender->sendMessage("§cError: No node specified.");
                        return;
                    }

                    $value = true;
                    if (!empty($args) && in_array(strtolower($args[0]), ['true', 'false'], true)) {
                        $value = strtolower(array_shift($args)) === 'true';
                    }

                    $durationStr = array_shift($args);
                    if ($durationStr === null) {
                        $sender->sendMessage("§cError: No duration specified.");
                        return;
                    }
                    $durationSeconds = Duration::fromString($durationStr)->getSeconds();

                    $modifier = array_shift($args) ?? 'replace';
                    if (!in_array($modifier, ['accumulate', 'replace', 'deny'], true)) $modifier = 'replace';

                    $success = $user->setTempPermission($user, $nodeString, $value, $durationSeconds, $modifier);

                    if ($success) {
                        $sender->sendMessage("§aTemporary permission '$nodeString' set to " . ($value ? "true" : "false") . " for " . Duration::betweenNowAnd($durationSeconds));
                    } else {
                        $sender->sendMessage("§cPermission '$nodeString' could not be set (denied by modifier).");
                    }
                });
                break;

            case "unsettemp":
                NovaPermsPlugin::getUserManager()->modifyUser($target, function (User $user) use ($sender, $args) {

                    $nodeString = array_shift($args);
                    if ($nodeString === null) {
                        $sender->sendMessage("§cError: No node specified.");
                        return;
                    }

                    $durationStr = array_shift($args) ?? null;
                    $durationSeconds = $durationStr !== null ? Duration::fromString($durationStr)->getSeconds() : null;

                    $success = $user->unsetTempPermission($user, $nodeString, $durationSeconds);

                    if ($success) {
                        if ($durationSeconds !== null) {
                            $sender->sendMessage("§aTemporary permission '$nodeString' unset and denied for " . Duration::betweenNowAnd($durationSeconds) . ".");
                        } else {
                            $sender->sendMessage("§aTemporary permission '$nodeString' unset successfully.");
                        }
                    } else {
                        $sender->sendMessage("§cPermission '$nodeString' not found or already expired.");
                    }
                });
                break;
            case "check":
                NovaPermsPlugin::getUserManager()->modifyUser($target, function (User $user) use ($sender, $args) {
                    $nodeString = array_shift($args);
                    if ($nodeString === null) {
                        $sender->sendMessage("§cError: No node specified.");
                        return;
                    }

                    $node = $user->findPermissionNode($nodeString);

                    if ($node !== null) {
                        $status = $node->getValue() ? "granted" : "denied";
                        $expiry = $node->getExpiry() !== -1 ? " (expires in " . Duration::betweenNowAnd($node->getExpiry() - time()) . ")" : "";
                        $sender->sendMessage("§aPermission '$nodeString' is $status$expiry.");
                    } else {
                        $sender->sendMessage("§cPermission '$nodeString' not found for user {$user->getName()}.");
                    }
                });
                break;
            case "clear":
                NovaPermsPlugin::getUserManager()->modifyUser($target, function (User $user) use ($sender) {
                    $permissions = $user->getOwnPermissionNodes();

                    if (empty($permissions)) {
                        $sender->sendMessage("§eUser {$user->getName()} has no permissions to clear.");
                        return;
                    }

                    $user->setPermissions([]);

                    $sender->sendMessage("§aAll permissions for user {$user->getName()} have been cleared.");
                });
                break;
        }
    }

    public function permissionForGroupHandler(CommandSender $sender, array $args, string $target): void
    {
        $sub = array_shift($args) ?? null;

        if ($sub === null) {
            $sender->sendMessage("error 3");
        }

        switch (strtolower($sub)) {
            case "info":
                break;
            case "set":
                break;
            case "unset":
                break;
        }
    }
}