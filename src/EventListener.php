<?php

namespace MohamadRZ\NovaPerms;

use MohamadRZ\NovaPerms\model\User;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener
{
    public function onLogin(PlayerPreLoginEvent $event): void
    {
        NovaPermsPlugin::getUserManager()->getOrMake($event->getPlayerInfo()->getUsername());
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        NovaPermsPlugin::getUserManager()->saveUser($event->getPlayer()->getName());
        NovaPermsPlugin::getUserManager()->cleanupUser($event->getPlayer()->getName());
    }
}