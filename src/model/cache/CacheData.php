<?php
namespace MohamadRZ\NovaPerms\model\cache;

class CacheData
{
    private mixed $data = null;
    private ?int $expiryTime = null;

    public function __construct(mixed $data = null, ?int $expiryTime = null)
    {
        $this->data = $data;
        $this->expiryTime = $expiryTime;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data, ?int $expiryTime = null): void
    {
        $this->data = $data;
        $this->expiryTime = $expiryTime;
    }

    public function isExpired(): bool
    {
        if ($this->expiryTime === null) {
            return false;
        }
        return time() >= $this->expiryTime;
    }

    public function clear(): void
    {
        $this->data = null;
        $this->expiryTime = null;
    }
}