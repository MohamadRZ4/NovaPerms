<?php

namespace MohamadRZ\StellarRanks;

use pocketmine\scheduler\Task;

class StellarTask extends Task
{

    public function onRun(): void
    {
        $players = StellarRanks::getInstance()->getServer()->getOnlinePlayers();
        foreach ($players as $player) {
            $ranks = StellarRanks::getPlayerConfig()->getPlayerRanks($player->getName());
            foreach ($ranks as $rankName => $rankData) {
                $expireTime = $rankData["expire"] ?? null;
                if ($expireTime !== null && time() > $expireTime) {
                    StellarRanks::getPlayerConfig()->removePlayerRank($player->getName(), $rankName);
                }
            }
        }
    }
}