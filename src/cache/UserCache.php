<?php

namespace MohamadRZ\StellarRanks\cache;

class UserCache extends BaseCache
{
    public function __construct()
    {
        parent::__construct('user');
    }

    public function setUser(int $userId, array $userData, int $ttl = -1): self
    {
        return $this->set("user_{$userId}", $userData, $ttl);
    }

    public function getUser(int $userId): ?array
    {
        return $this->get("user_{$userId}");
    }

    public function deleteUser(int $userId): self
    {
        return $this->delete("user_{$userId}");
    }

    public function setUserStats(int $userId, array $stats, int $ttl = 1800): self
    {
        return $this->set("stats_{$userId}", $stats, $ttl);
    }

    public function getUserStats(int $userId): ?array
    {
        return $this->get("stats_{$userId}");
    }
}