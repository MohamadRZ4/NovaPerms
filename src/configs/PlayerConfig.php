<?php

namespace MohamadRZ\StellarRanks\configs;

use JsonException;
use MohamadRZ\StellarRanks\StellarRanks;
use pocketmine\utils\Config;

class PlayerConfig
{
    private Config $config;

    public function __construct(string $dataPath)
    {
        $this->config = new Config($dataPath . "players.yml", Config::YAML);
    }

    public function initPlayer(string $playerName): bool
    {
        if ($this->config->exists($playerName)) return false;

        $data = [
            "ranks" => [], // [rankName => [expire => timestamp|null]]
            "permissions" => []
        ];

        $this->config->set($playerName, $data);
        $this->config->save();
        return true;
    }

    public function addPlayerRank(string $playerName, string $rankName, ?int $expire = null): bool
    {
        if (!$this->config->exists($playerName)) {
            $this->initPlayer($playerName);
        }

        $data = $this->config->get($playerName);
        if (isset($data["ranks"][$rankName])) {
            return false;
        }

        $data["ranks"][$rankName] = ["expire" => $expire];
        $this->config->set($playerName, $data);
        $this->config->save();
        return true;
    }

    public function removePlayerRank(string $playerName, string $rankName): bool
    {
        if (!$this->config->exists($playerName)) return false;

        $data = $this->config->get($playerName);
        if (!isset($data["ranks"][$rankName])) {
            return false;
        }

        unset($data["ranks"][$rankName]);
        $this->config->set($playerName, $data);
        $this->config->save();
        return true;
    }

    public function getPlayerRanks(string $playerName): ?array
    {
        if (!$this->config->exists($playerName)) return null;

        return $this->config->get($playerName)["ranks"] ?? [];
    }

    public function getHighestRank(string $playerName): ?string
    {
        if (!$this->config->exists($playerName)) return null;

        $ranks = $this->getPlayerRanks($playerName);
        $highestRank = null;
        $highestWeight = -1;

        foreach ($ranks as $rankName => $data) {
            $rankInfo = StellarRanks::getRanksConfig()->getRank($rankName);
            if ($rankInfo === null) continue;

            $weight = $rankInfo["weight"] ?? 0;
            if ($weight > $highestWeight) {
                $highestWeight = $weight;
                $highestRank = $rankName;
            }
        }

        return $highestRank;
    }

    public function getAll(): array
    {
        return $this->config->getAll();
    }

    /**
     * @throws JsonException
     */
    public function save(): void
    {
        $this->config->save();
    }
}