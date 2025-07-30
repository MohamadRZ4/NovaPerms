<?php

namespace MohamadRZ\NovaPerms\model;


use MohamadRZ\NovaPerms\model\cache\CachedLoader;
use MohamadRZ\NovaPerms\model\cache\CacheInstance;
use MohamadRZ\NovaPerms\node\AbstractNode;

class NodeManager
{
    private CacheInstance $cache;
    private const CACHE_PREFIX = 'permissions:';
    private const USER_PREFIX = 'user:';
    private const GROUP_PREFIX = 'group:';

    public function __construct()
    {
        $this->cache = CachedLoader::create('nodes')
            ->memory()
            ->permanent()
            ->build();
    }

    public function addNode(string $holderType, string $holderId, AbstractNode $node): void
    {
        $key = $this->getKey($holderType, $holderId);
        $nodes = $this->getNodes($holderType, $holderId);

        if (!in_array($node, $nodes)) {
            $nodes[] = $node;
            $this->cache->set($key, $nodes);
        }
    }

    public function removeNode(string $holderType, string $holderId, AbstractNode $node): void
    {
        $key = $this->getKey($holderType, $holderId);
        $nodes = $this->getNodes($holderType, $holderId);

        $index = array_search($node, $nodes);
        if ($index !== false) {
            unset($nodes[$index]);
            $nodes = array_values($nodes);
            $this->cache->set($key, $nodes);
        }
    }

    public function getNodes(string $holderType, string $holderId): array
    {
        $key = $this->getKey($holderType, $holderId);
        return $this->cache->get($key, []);
    }

    public function setNodes(string $holderType, string $holderId, array $nodes): void
    {
        $key = $this->getKey($holderType, $holderId);
        $this->cache->set($key, array_unique($nodes));
    }

    public function clearNodes(string $holderType, string $holderId): void
    {
        $key = $this->getKey($holderType, $holderId);
        $this->cache->delete($key);
    }

    public function getNodeCount(string $holderType, string $holderId): int
    {
        return count($this->getNodes($holderType, $holderId));
    }

    private function getKey(string $holderType, string $holderId): string
    {
        $prefix = match($holderType) {
            'user' => self::USER_PREFIX,
            'group' => self::GROUP_PREFIX,
            default => $holderType . ':'
        };

        return self::CACHE_PREFIX . $prefix . $holderId;
    }

    public function getCacheStats(): array
    {
        return $this->cache->getProvider()->getStats();
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }
}
