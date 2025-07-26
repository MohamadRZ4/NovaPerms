<?php

namespace MohamadRZ\StellarRanks;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\chat\LegacyRawChatFormatter;

class StellarListener implements Listener
{
    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        if (StellarRanks::getPlayerConfig()->initPlayer($playerName)) {
            $defaultRank = StellarRanks::getConfigManager()->getDefaultRank();
            StellarRanks::getPlayerConfig()->addPlayerRank($playerName, $defaultRank);
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        //todo
    }
}
