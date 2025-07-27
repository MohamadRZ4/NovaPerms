<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file\loaders;

use pocketmine\utils\Config;

class YamlLoader implements ConfigurateLoader
{

    public function getExtension(): string
    {
        return ".yml";
    }

    public function load(string $filePath): Config
    {
        return new Config($filePath, Config::YAML);
    }
}