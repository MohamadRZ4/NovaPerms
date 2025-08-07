<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\node\Types\PermissionNode;
use MohamadRZ\NovaPerms\node\Types\WeightNode;
use MohamadRZ\NovaPerms\node\Types\PrefixNode;
use MohamadRZ\NovaPerms\node\Types\SuffixNode;
use MohamadRZ\NovaPerms\node\Types\MetaNode;
use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

abstract class PermissionHolder
{
    protected array $nodes = [];
    protected array $cachedNodes = [];
    protected ?string $lastContextHash = null;

    public function importNodes($nodes): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof AbstractNode) {
                $this->setNode($node);
            }
        }
    }

    public function setNode(AbstractNode $node): void
    {
        $this->nodes[] = $node;
        $this->clearCache();
    }

    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getAllNodes(bool $resolveInheritance = false, ?ContextSet $playerContext = null): array
    {
        if (!$resolveInheritance) {
            return $this->filterNodesByContext($this->nodes, $playerContext);
        }

        $contextHash = $this->getContextHash($playerContext);

        if ($this->lastContextHash === $contextHash && !empty($this->cachedNodes)) {
            return $this->cachedNodes;
        }

        $this->cachedNodes = $this->resolveNodesWithInheritance($playerContext);
        $this->lastContextHash = $contextHash;

        return $this->cachedNodes;
    }

    private function resolveNodesWithInheritance(?ContextSet $playerContext = null, array &$visited = []): array
    {
        $result = $this->filterNodesByContext($this->nodes, $playerContext);

        $id = $this->getId();
        if (isset($visited[$id])) {
            return [];
        }
        $visited[$id] = true;

        foreach ($this->nodes as $node) {
            if (!($node instanceof InheritanceNode) || !$this->shouldApplyNode($node, $playerContext)) {
                continue;
            }

            $groupName = $node->getGroup();
            if ($groupName === $this->getName()) {
                continue;
            }

            $parent = NovaPermsPlugin::getInstance()->getGroupManager()->getIfLoaded($groupName);
            if ($parent instanceof Group) {
                $result = array_merge($result, $parent->resolveNodesWithInheritance($playerContext, $visited));
            }
        }

        return $result;
    }

    private function filterNodesByContext(array $nodes, ?ContextSet $playerContext): array
    {
        if ($playerContext === null) {
            return $nodes;
        }

        return array_filter($nodes, fn(AbstractNode $node) => $this->shouldApplyNode($node, $playerContext));
    }

    private function shouldApplyNode(AbstractNode $node, ?ContextSet $playerContext): bool
    {
        if ($playerContext === null) {
            return true;
        }

        $nodeContexts = $node->getContext();
        if ($nodeContexts === null || $nodeContexts->isEmpty()) {
            return true;
        }

        foreach ($nodeContexts->getContexts() as $requiredContext) {
            $key = $requiredContext->getKey();
            $requiredValue = $requiredContext->getValue();

            if (!$playerContext->containsKey($key)) {
                return false;
            }

            if (!in_array($requiredValue, $playerContext->getValues($key), true)) {
                return false;
            }
        }

        return true;
    }

    public function unsetNode(AbstractNode $target): void
    {
        $this->nodes = array_filter($this->nodes, fn($node) => $node !== $target);
        $this->clearCache();
    }

    public function clearCache(): void
    {
        $this->cachedNodes = [];
        $this->lastContextHash = null;
    }

    private function getContextHash(?ContextSet $playerContext): string
    {
        if ($playerContext === null) {
            return 'null';
        }

        return md5(serialize($playerContext));
    }

    private function getId(): string
    {
        return $this instanceof Group ? $this->getName() : spl_object_id($this);
    }

    private function getName(): ?string
    {
        return $this instanceof Group ? $this->getName() : null;
    }

    public function getPrefixes(bool $resolveInheritance = true, ?ContextSet $playerContext = null): array
    {
        $allNodes = $this->getAllNodes($resolveInheritance, $playerContext);
        $prefixes = [];

        foreach ($allNodes as $node) {
            if ($node instanceof PrefixNode && $node->getValue()) {
                $prefixes[] = $node;
            }
        }

        usort($prefixes, function(PrefixNode $a, PrefixNode $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $prefixes;
    }

    public function getSuffixes(bool $resolveInheritance = true, ?ContextSet $playerContext = null): array
    {
        $allNodes = $this->getAllNodes($resolveInheritance, $playerContext);
        $suffixes = [];

        foreach ($allNodes as $node) {
            if ($node instanceof SuffixNode && $node->getValue()) {
                $suffixes[] = $node;
            }
        }

        usort($suffixes, function(SuffixNode $a, SuffixNode $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $suffixes;
    }

    public function getHighestPrefix(bool $resolveInheritance = true, ?ContextSet $playerContext = null): ?PrefixNode
    {
        $prefixes = $this->getPrefixes($resolveInheritance, $playerContext);
        return empty($prefixes) ? null : $prefixes[0];
    }

    public function getHighestSuffix(bool $resolveInheritance = true, ?ContextSet $playerContext = null): ?SuffixNode
    {
        $suffixes = $this->getSuffixes($resolveInheritance, $playerContext);
        return empty($suffixes) ? null : $suffixes[0];
    }

    public function getPrefixValue(bool $resolveInheritance = true, ?ContextSet $playerContext = null): string
    {
        $prefix = $this->getHighestPrefix($resolveInheritance, $playerContext);
        return $prefix !== null ? $prefix->getPrefix() : '';
    }

    public function getSuffixValue(bool $resolveInheritance = true, ?ContextSet $playerContext = null): string
    {
        $suffix = $this->getHighestSuffix($resolveInheritance, $playerContext);
        return $suffix !== null ? $suffix->getSuffix() : '';
    }

    public function searchNodeByKey(string $key, bool $resolveInheritance = false, ?ContextSet $playerContext = null): ?AbstractNode
    {
        return array_find($this->getAllNodes($resolveInheritance, $playerContext), fn($node) => $node instanceof AbstractNode && $node->getKey() === $key);
    }

    public function searchNodesByKey(string $key, bool $resolveInheritance = false, ?ContextSet $playerContext = null): array
    {
        $results = [];
        foreach ($this->getAllNodes($resolveInheritance, $playerContext) as $node) {
            if (method_exists($node, 'getKey') && $node->getKey() === $key) {
                $results[] = $node;
            }
        }
        return $results;
    }

}
