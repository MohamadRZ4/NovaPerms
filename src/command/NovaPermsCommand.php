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
                case "group":
                    $this->groupHandler($sender, $args);
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
                $this->permissionHandler($sender, $args, $target, "user");
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
                $this->permissionHandler($sender, $args, $group, "group");
                break;
        }
    }

    public function permissionHandler(
        CommandSender $sender,
        array $args,
        string $target,
        string $type // 'user' | 'group'
    ): void {
        $sub = array_shift($args);
        if ($sub === null) {
            $sender->sendMessage("error 3");
            return;
        }

        $isUser = $type === 'user';

        $loader = function (callable $callback) use ($isUser, $target, $sender) {
            if ($isUser) {
                NovaPermsPlugin::getUserManager()->modifyUser($target, $callback);
            } else {
                $group = NovaPermsPlugin::getGroupManager()->getGroup($target);
                if ($group === null) {
                    $sender->sendMessage("§cGroup not found.");
                    return;
                }
                $callback($group);
            }
        };

        switch (strtolower($sub)) {

            /* ================= INFO ================= */
            case "info":
                $loader(function ($holder) use ($sender) {
                    $permissions = $holder->getPermissions();

                    $sender->sendMessage(TF::GOLD . str_repeat("-", 30));
                    $sender->sendMessage(
                        TF::YELLOW . " NovaPerms " .
                        TF::GRAY . "Info {$holder->getName()}"
                    );
                    $sender->sendMessage(TF::GOLD . str_repeat("-", 30));

                    if (empty($permissions)) {
                        $sender->sendMessage(TF::GRAY . "No permissions assigned.");
                    } else {
                        foreach ($permissions as $key => $node) {
                            $expiry = $node->getExpiry();

                            if ($expiry === -1) {
                                $expiryText = TF::GREEN . "never";
                            } elseif ($expiry <= time()) {
                                $expiryText = TF::DARK_RED . "expired";
                            } else {
                                $expiryText =
                                    TF::RED . date("Y-m-d H:i:s", $expiry) .
                                    TF::GRAY . " (" .
                                    Duration::betweenNowAnd($expiry)->format() .
                                    " left)";
                            }

                            $sender->sendMessage(
                                TF::AQUA . " - " . TF::WHITE . $key .
                                TF::DARK_GRAY . " | " .
                                TF::GREEN . ($node->getValue() ? "true" : "false") .
                                TF::DARK_GRAY . " | " .
                                TF::GRAY . "expires: " . $expiryText
                            );
                        }
                    }

                    $sender->sendMessage(TF::GOLD . str_repeat("-", 30));
                });
                break;

            /* ================= SET ================= */
            case "set":
                $loader(function ($holder) use ($sender, $args) {
                    $node = array_shift($args);
                    if ($node === null) {
                        $sender->sendMessage("error 4");
                        return;
                    }

                    $value = true;
                    if (!empty($args) && in_array(strtolower($args[0]), ['true', 'false'], true)) {
                        $value = strtolower(array_shift($args)) === 'true';
                    }

                    $holder->addPermission($node, $value);
                    $sender->sendMessage("Permission '$node' set to " . ($value ? "true" : "false"));
                });
                break;

            /* ================= UNSET ================= */
            case "unset":
                $loader(function ($holder) use ($sender, $args) {
                    $node = array_shift($args);
                    if ($node === null) {
                        $sender->sendMessage("error: No node specified.");
                        return;
                    }

                    if ($holder->removePermission($node)) {
                        $sender->sendMessage("Permission '$node' unset successfully.");
                    } else {
                        $sender->sendMessage("Permission '$node' not found.");
                    }
                });
                break;

            /* ================= SETTEMP ================= */
            case "settemp":
                $loader(function ($holder) use ($sender, $args) {
                    $node = array_shift($args);
                    if ($node === null) {
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

                    $seconds = Duration::fromString($durationStr)->getSeconds();
                    $modifier = array_shift($args) ?? 'replace';
                    if (!in_array($modifier, ['accumulate', 'replace', 'deny'], true)) {
                        $modifier = 'replace';
                    }

                    $success = $holder->setTempPermission(
                        $holder,
                        $node,
                        $value,
                        $seconds,
                        $modifier
                    );

                    if ($success) {
                        $sender->sendMessage(
                            "§aTemporary permission '$node' set to " .
                            ($value ? "true" : "false") .
                            " for " . Duration::ofSeconds($seconds)
                        );
                    } else {
                        $sender->sendMessage("§cPermission '$node' denied by modifier.");
                    }
                });
                break;

            /* ================= UNSETTEMP ================= */
            case "unsettemp":
                $loader(function ($holder) use ($sender, $args) {
                    $node = array_shift($args);
                    if ($node === null) {
                        $sender->sendMessage("§cError: No node specified.");
                        return;
                    }

                    $durationStr = array_shift($args);
                    $seconds = $durationStr !== null
                        ? Duration::fromString($durationStr)->getSeconds()
                        : null;

                    if ($holder->unsetTempPermission($holder, $node, $seconds)) {
                        $sender->sendMessage("§aTemporary permission '$node' unset.");
                    } else {
                        $sender->sendMessage("§cPermission '$node' not found.");
                    }
                });
                break;

            /* ================= CHECK ================= */
            case "check":
                $loader(function ($holder) use ($sender, $args) {
                    $node = array_shift($args);
                    if ($node === null) {
                        $sender->sendMessage("§cError: No node specified.");
                        return;
                    }

                    $perm = $holder->findPermissionNode($node);
                    if ($perm !== null) {
                        $sender->sendMessage(
                            "§aPermission '$node' is " .
                            ($perm->getValue() ? "granted" : "denied")
                        );
                    } else {
                        $sender->sendMessage("§cPermission '$node' not found.");
                    }
                });
                break;

            /* ================= CLEAR ================= */
            case "clear":
                $loader(function ($holder) use ($sender) {
                    if (empty($holder->getOwnPermissionNodes())) {
                        $sender->sendMessage("§eNo permissions to clear.");
                        return;
                    }

                    $holder->setPermissions([]);
                    $sender->sendMessage("§aAll permissions cleared.");
                });
                break;
        }
    }

}