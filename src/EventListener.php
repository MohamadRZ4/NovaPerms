<?php

namespace MohamadRZ\NovaPerms;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

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