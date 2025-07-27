<?php

namespace MohamadRZ\NovaPerms\model\cache;

use MohamadRZ\NovaPerms\model\cache\config\CacheConfig;
use MohamadRZ\NovaPerms\model\cache\providers\providers\FileCacheProvider;

class CacheLoaderBuilder
{
    private string $provider = 'memory';
    private ?CacheConfig $config = null;

    public function __construct(private readonly string $name) {}

    public function memory(): self
    {
        $this->provider = 'memory';
        return $this;
    }

    public function file(string $cacheDir = null): self
    {
        $this->provider = 'file';
        $this->config = CacheConfig::create()
            ->option('cache_dir', $cacheDir)
            ->build();
        return $this;
    }

    public function permanent(): self
    {
        if ($this->config === null) {
            $this->config = CacheConfig::create()->permanent()->build();
        }
        return $this;
    }

    public function noExpiry(): self
    {
        return $this->permanent();
    }

    public function provider(string $providerClass): self
    {
        $this->provider = $providerClass;
        return $this;
    }

    public function config(CacheConfig $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function build(): CacheInstance
    {
        $config = $this->config ?? CacheConfig::create()->permanent()->build();

        $provider = match($this->provider) {
            'memory' => new MemoryCacheProvider($config),
            'file' => new FileCacheProvider($config),
            default => new $this->provider($config)
        };

        CachedLoader::register($this->name, $provider);
        return CachedLoader::get($this->name);
    }
}