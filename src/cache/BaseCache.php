<?php

namespace MohamadRZ\StellarRanks\cache;

abstract class BaseCache
{
    protected array $storage = [];
    protected array $expiration = [];
    protected string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function set(string $key, mixed $value, int $ttl = -1): self
    {
        $this->storage[$key] = $value;
        $this->expiration[$key] = $ttl === -1 ? -1 : time() + $ttl;
        return $this;
    }

    public function get(string $key): mixed
    {
        if ($this->isExpired($key)) {
            $this->delete($key);
            return null;
        }

        return $this->storage[$key] ?? null;
    }

    public function delete(string $key): self
    {
        unset($this->storage[$key], $this->expiration[$key]);
        return $this;
    }

    public function has(string $key): bool
    {
        return !$this->isExpired($key) && isset($this->storage[$key]);
    }

    public function flush(): self
    {
        $this->storage = [];
        $this->expiration = [];
        return $this;
    }

    public function cleanup(): self
    {
        foreach ($this->expiration as $key => $exp) {
            if ($this->isExpired($key)) {
                $this->delete($key);
            }
        }
        return $this;
    }

    private function isExpired(string $key): bool
    {
        $exp = $this->expiration[$key] ?? null;
        return $exp !== null && $exp !== -1 && $exp <= time();
    }

    public function getAll(): array
    {
        $this->cleanup();
        return $this->storage;
    }

    public function count(): int
    {
        $this->cleanup();
        return count($this->storage);
    }
}