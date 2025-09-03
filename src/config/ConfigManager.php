<?php

namespace MohamadRZ\NovaPerms\config;

use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\utils\Config;

class ConfigManager
{

    private Config $config;
    public function __construct(NovaPermsPlugin $plugin, string $path)
    {
        $this->config = new Config($path . "config.yml", Config::YAML);
    }

    public function getStorageType()
    {
        return $this->config->get("storage-type", "yml");
    }
}