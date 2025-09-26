<?php

namespace MohamadRZ\NovaPerms\commands\user;

use MohamadRZ\CommandLib\BaseCommand;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
class UserInfo extends BaseCommand
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

    /**
     * @param CommandSender $sender
     * @param array $args
     * @param string $rootLabel
     * @return void
     */
    #[\Override] protected function onRun(CommandSender $sender, array $args, string $rootLabel): void
    {
        $targetPlayerName = $this->getArg("name");

        if ($targetPlayerName === null) {
            $sender->sendMessage("§cNo target player specified!");
            return;
        }

        $targetPlayer = Server::getInstance()->getPlayerExact($targetPlayerName);
        $isOnline = $targetPlayer !== null;

        if ($isOnline) {
            $playerName = $targetPlayer->getName();
            $playerXuid = $targetPlayer->getXuid();
            $onlineStatus = "§aOnline";
        } else {
            $playerName = $targetPlayerName;
            $playerXuid = "N/A (Offline)";
            $onlineStatus = "§cOffline";
        }

        $user = NovaPermsPlugin::getUserManager()->getUser($playerName);

        $message =  NovaPermsPlugin::PREFIX. " §l§b> §r§bUser Info: §f" . $playerName;
        $message .= NovaPermsPlugin::PREFIX. "\n §f§l- §r§3XUID: §f" . $playerXuid;
        $message .= NovaPermsPlugin::PREFIX. "\n §f§l- §r§3Status: " . $onlineStatus;

        if ($user !== null) {
            $message .= NovaPermsPlugin::PREFIX. "\n §f§l- §r§aParent Groups:";
            foreach ($user->getInheritances() as $inheritance) {
                $message .= NovaPermsPlugin::PREFIX. "\n    §3> §f{$inheritance->getGroup()}";
            }
        } else {
            $message .= "\n §f§l- §r§cNo permission data found";
        }

        $sender->sendMessage($message);
        return;
    }
}