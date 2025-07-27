<?php

namespace MohamadRZ\NovaPerms\model\cache\config;

class CacheConfigBuilder
{
    private string $prefix = '';
    private ?int $defaultTtl = null;
    private bool $serialize = true;
    private string $serializer = 'php';
    private bool $compression = false;
    private bool $enableExpiry = true;
    private array $options = [];

    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function ttl(?int $ttl): self
    {
        $this->defaultTtl = $ttl;
        return $this;
    }

    public function noExpiry(): self
    {
        $this->enableExpiry = false;
        $this->defaultTtl = null;
        return $this;
    }

    public function permanent(): self
    {
        return $this->noExpiry();
    }

    public function serializer(string $type): self
    {
        $this->serializer = $type;
        return $this;
    }

    public function compression(bool $enable = true): self
    {
        $this->compression = $enable;
        return $this;
    }

    public function option(string $key, mixed $value): self
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function build(): CacheConfig
    {
        return new CacheConfig(
            $this->prefix,
            $this->defaultTtl,
            $this->serialize,
            $this->serializer,
            $this->compression,
            $this->enableExpiry,
            $this->options
        );
    }
}