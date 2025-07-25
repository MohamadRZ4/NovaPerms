<?php

namespace MohamadRZ\StellarRanks\cache;

class Cache
{
    private UserCache $userCache;
    private RankCache $rankCache;
    private TrackCache $trackCache;

    public function __construct()
    {
        $this->userCache = new UserCache();
        $this->rankCache = new RankCache();
        $this->trackCache = new TrackCache();
    }

    public function users(): UserCache
    {
        return $this->userCache;
    }

    public function ranks(): RankCache
    {
        return $this->rankCache;
    }

    public function tracks(): TrackCache
    {
        return $this->trackCache;
    }

    public function flushAll(): self
    {
        $this->userCache->flush();
        $this->rankCache->flush();
        $this->trackCache->flush();
        return $this;
    }

    public function cleanupAll(): self
    {
        $this->userCache->cleanup();
        $this->rankCache->cleanup();
        $this->trackCache->cleanup();
        return $this;
    }

    public function getStats(): array
    {
        return [
            'users' => $this->userCache->count(),
            'ranks' => $this->rankCache->count(),
            'tracks' => $this->trackCache->count(),
            'total' => $this->userCache->count() + $this->rankCache->count() + $this->trackCache->count()
        ];
    }
}