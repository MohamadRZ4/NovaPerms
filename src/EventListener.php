<?php

namespace MohamadRZ\NovaPerms;

use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\node\Types\RegexPermission;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;

class EventListener implements Listener
{
    public function onLogin(PlayerLoginEvent $event): void
    {
        $username = $event->getPlayer()->getName();
        NovaPermsPlugin::getUserManager()->loadUser($username)->onCompletion(
            function($user) {
                $user->setIsInitialized(true);
                Server::getInstance()->getLogger()->info("User {$user->getName()} loaded.");
            },
            function() use ($username) {
                Server::getInstance()->getLogger()->warning("Failed to load user {$username} from database.");
            }
        );;
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $user = NovaPermsPlugin::getUserManager()->getUser($player->getName());
        $user->addPermission(new RegexPermission("pocketmine.*"));
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        NovaPermsPlugin::getUserManager()->saveUser($event->getPlayer()->getName());
        NovaPermsPlugin::getUserManager()->cleanupUser($event->getPlayer()->getName());
    }
}