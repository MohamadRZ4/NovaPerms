<?php

namespace MohamadRZ\NovaPerms;

use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\Types\RegexPermission;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\Server;

class EventListener implements Listener
{
    public function onLogin(PlayerLoginEvent $event): void
    {
        $username = strtolower($event->getPlayer()->getName());
        NovaPermsPlugin::getUserManager()->loadUser($username)->onCompletion(
            function(User $user) {
                $user->setIsInitialized(true);
                $user->updatePermissions();
                Server::getInstance()->getLogger()->info("User {$user->getName()} loaded.");
            },
            function() use ($username) {
                Server::getInstance()->getLogger()->warning("Failed to load user {$username} from database.");
            }
        );
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $username = strtolower($event->getPlayer()->getName());
        NovaPermsPlugin::getUserManager()->saveUser($username);
        NovaPermsPlugin::getUserManager()->cleanupUser($event->getPlayer()->getName());
    }
}