<?php

namespace MohamadRZ\StellarRanks\configs;

use AllowDynamicProperties;
use pocketmine\utils\Config;

#[AllowDynamicProperties] class RanksConfig
{
    public function __construct(string $dataPath)
    {
        $this->config = new Config($dataPath . "ranks.yml", Config::YAML, [
            "guest" => [
                "displayName" => "Guest",
                "prefix" => "[Guest]",
                "suffix" => "",
                "weight" => 0,
                "permissions" => []
            ]
        ]);
    }

    public function addRank(string $name): bool
    {
        if ($this->config->exists($name)) return false;

        $data = [
            "displayName" => $name,
            "prefix" => "[$name]",
            "suffix" => "",
            "weight" => 0,
            "permissions" => []
        ];

        $this->config->set($name, $data);
        return true;
    }

    public function removeRank(string $name): bool
    {
        if ($this->config->exists($name))
        {
            $this->config->remove($name);
            return true;
        }
        return false;
    }

    public function getRank(string $name): ?array
    {
        if ($this->config->exists($name)) {
            return $this->config->get($name);
        }
        return null;
    }
}