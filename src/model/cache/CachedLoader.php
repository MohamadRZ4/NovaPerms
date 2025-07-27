<?php
namespace MohamadRZ\NovaPerms\model\cache;
class CachedLoader
{
    private string $username;
    private CacheData $cacheData;
    private int $lastAccess;
    private bool $useExpiry;

    public function __construct(string $username, bool $useExpiry = true)
    {
        $this->username = strtolower($username);
        $this->useExpiry = $useExpiry;
        $this->cacheData = new CacheData();
        $this->lastAccess = time();
    }

    public function loadData(mixed $data, ?int $expiryTime = null): void
    {
        $this->cacheData->setData($data, $this->useExpiry ? $expiryTime : null);
        $this->lastAccess = time();
    }

    public function get(): mixed
    {
        if ($this->cacheData->getData() !== null && (!$this->useExpiry || !$this->cacheData->isExpired())) {
            $this->lastAccess = time();
            return $this->cacheData->getData();
        }
        return null;
    }

    public function clear(): void
    {
        $this->cacheData->clear();
        $this->lastAccess = time();
    }

    public function isExpired(): bool
    {
        if (!$this->useExpiry) {
            return false;
        }
        return $this->cacheData->isExpired();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getLastAccess(): int
    {
        return $this->lastAccess;
    }
}