<?php

namespace MohamadRZ\StellarRanks\configs;

use AllowDynamicProperties;
use pocketmine\utils\Config;

#[AllowDynamicProperties] class ConfigManager
{

    public function __construct(string $dataPath)
    {
        $this->dataPath = $dataPath;
        $this->config = new Config($dataPath . "config.yml", Config::YAML, ["default_rank" => "guest"]);
    }

    public function getDefaultRank(): string
    {
        return $this->config->get("default_rank");
    }
}
