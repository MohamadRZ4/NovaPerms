<?php

namespace MohamadRZ\NovaPerms\config;

use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\utils\Config;

class ConfigManager
{

    private Config $config;
    public function __construct(NovaPermsPlugin $plugin, string $path)
    {
        $plugin->getLogger()->info("Loading configuration...");
        $this->config = new Config($path . "config.yml", Config::YAML);
    }

    public function getDatabase()
    {
        return $this->config->get("database");
    }

    public function getPrimaryGroupCalculation()
    {
        return $this->config->get("primary-group-calculation");
    }
}