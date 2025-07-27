<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file\loaders;

use pocketmine\utils\Config;

interface ConfigurateLoader
{
    public function getExtension(): string;

    public function load(string $filePath): Config;
}