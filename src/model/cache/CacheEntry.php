<?php

namespace MohamadRZ\NovaPerms\model\cache;

class CacheEntry
{
    private mixed $data;
    private ?int $expiryTime;
    private int $lastAccess;

    public function __construct(mixed $data, ?int $expiryTime = null)
    {
        $this->data = $data;
        $this->expiryTime = $expiryTime;
        $this->lastAccess = time();
    }

    public function getData(): mixed
    {
        $this->lastAccess = time();
        return $this->data;
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
        $this->lastAccess = time();
    }

    public function isExpired(): bool
    {
        if ($this->expiryTime === null) {
            return false;
        }
        return time() >= $this->expiryTime;
    }

    public function getLastAccess(): int
    {
        return $this->lastAccess;
    }
}