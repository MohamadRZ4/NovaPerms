<?php

namespace MohamadRZ\NovaPerms\model\cache\providers;

use MohamadRZ\NovaPerms\model\cache\config\CacheConfig;
use MohamadRZ\NovaPerms\model\cache\interfaces\CacheProviderInterface;

abstract class AbstractCacheProvider implements CacheProviderInterface
{
    protected CacheConfig $config;
    protected array $stats = ['hits' => 0, 'misses' => 0, 'sets' => 0, 'deletes' => 0];

    public function __construct(CacheConfig $config)
    {
        $this->config = $config;
        $this->initialize();
    }

    abstract protected function initialize(): void;
    abstract protected function doGet(string $key): mixed;
    abstract protected function doSet(string $key, mixed $value, ?int $ttl): bool;
    abstract protected function doDelete(string $key): bool;
    abstract protected function doClear(): bool;
    abstract protected function doExists(string $key): bool;

    public function get(string $key, mixed $default = null): mixed
    {
        $prefixedKey = $this->getPrefixedKey($key);
        $value = $this->doGet($prefixedKey);

        if ($value === null) {
            $this->stats['misses']++;
            return $default;
        }

        $this->stats['hits']++;
        return $this->unserialize($value);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $prefixedKey = $this->getPrefixedKey($key);
        $serializedValue = $this->serialize($value);

        if (!$this->config->enableExpiry) {
            $ttl = null;
        } else {
            $ttl = $ttl ?? $this->config->defaultTtl;
        }

        $result = $this->doSet($prefixedKey, $serializedValue, $ttl);
        if ($result) {
            $this->stats['sets']++;
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        $prefixedKey = $this->getPrefixedKey($key);
        $result = $this->doDelete($prefixedKey);
        if ($result) {
            $this->stats['deletes']++;
        }
        return $result;
    }

    public function clear(): bool
    {
        return $this->doClear();
    }

    public function exists(string $key): bool
    {
        $prefixedKey = $this->getPrefixedKey($key);
        return $this->doExists($prefixedKey);
    }

    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(array $items, ?int $ttl = null): bool
    {
        if (!$this->config->enableExpiry) {
            $ttl = null;
        }

        $success = true;
        foreach ($items as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    public function increment(string $key, int $value = 1): int|false
    {
        $current = $this->get($key, 0);
        if (!is_numeric($current)) {
            return false;
        }

        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->increment($key, -$value);
    }

    protected function getPrefixedKey(string $key): string
    {
        return $this->config->prefix ? $this->config->prefix . ':' . $key : $key;
    }

    protected function serialize(mixed $value): mixed
    {
        if (!$this->config->serialize) {
            return $value;
        }

        $serialized = match($this->config->serializer) {
            'json' => json_encode($value),
            'igbinary' => igbinary_serialize($value),
            default => serialize($value)
        };

        if ($this->config->compression && function_exists('gzcompress')) {
            $serialized = gzcompress($serialized);
        }

        return $serialized;
    }

    protected function unserialize(mixed $value): mixed
    {
        if (!$this->config->serialize) {
            return $value;
        }

        if ($this->config->compression && function_exists('gzuncompress')) {
            $value = gzuncompress($value);
        }

        return match($this->config->serializer) {
            'json' => json_decode($value, true),
            'igbinary' => igbinary_unserialize($value),
            default => unserialize($value)
        };
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    protected function isExpiryEnabled(): bool
    {
        return $this->config->enableExpiry;
    }
}
