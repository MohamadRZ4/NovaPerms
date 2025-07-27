<?php

namespace MohamadRZ\NovaPerms\model\cache\config;

class CacheConfig
{
    public function __construct(
        public readonly string $prefix = '',
        public readonly ?int $defaultTtl = null,
        public readonly bool $serialize = true,
        public readonly string $serializer = 'php',
        public readonly bool $compression = false,
        public readonly bool $enableExpiry = true,
        public readonly array $options = []
    ) {}

    public static function create(): CacheConfigBuilder
    {
        return new CacheConfigBuilder();
    }
}