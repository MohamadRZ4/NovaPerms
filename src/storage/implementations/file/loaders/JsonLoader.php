<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file\loaders;

use pocketmine\utils\Config;

class JsonLoader implements ConfigurateLoader
{
    public function getExtension(): string
    {
        return '.json';
    }

    public function load(string $filePath): Config
    {
         return new Config($filePath, Config::JSON);
    }
}
