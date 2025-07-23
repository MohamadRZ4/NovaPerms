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

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $playerName = $player->getName();

        $rankName = StellarRanks::getPlayerConfig()->getHighestRank($playerName);
        $rank = StellarRanks::getRanksConfig()->getRank($rankName);
        $chatPrefix = $rank["prefix"]["chat"];
        $chatSuffix = $rank["suffix"]["chat"];
        $chatFormat = $rank["chatFormat"];
         
    }

}