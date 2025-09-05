<?php

namespace MohamadRZ\NovaPerms;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;

class EventListener implements Listener
{
    public function onLogin(PlayerLoginEvent $event)
    {
        $player = $event->getPlayer();
        $manager = NovaPermsPlugin::getUserManager();
        $user = $manager->loadUser($player->getName());
        $user->updatePermissions();
    }
}