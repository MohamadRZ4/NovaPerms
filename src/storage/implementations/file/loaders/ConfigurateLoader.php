<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file\loaders;

use pocketmine\utils\Config;

interface ConfigurateLoader
{
    /**
     * Get the file extension
     */
    public function getExtension(): string;

    /**
     * Load data from file
     */
    public function load(string $filePath): Config;
}