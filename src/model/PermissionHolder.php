<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\Node;
use MohamadRZ\NovaPerms\node\serialize\NodeDeserializer;
use MohamadRZ\NovaPerms\node\Types\PermissionNodeBuilder;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\permission\PermissionManager;

abstract class PermissionHolder
{
    private array $nodes = [];
    /** @var InheritanceNode[] */
    protected array $inheritances = [];

    // ==================== Permission Management ====================

    public function setNode(Node $node): void
    {
        if ($node instanceof InheritanceNode) {
            $this->addInheritance($node);
        }

        $storageKey = $this->makeStorageKey($node);
        $this->nodes[$storageKey] = $node;
    }

    public function unsetNode(Node $node): bool
    {
        $removed = false;

        foreach ($this->nodes as $key => $existing) {
            if (
                $existing->getKey() === $node->getKey() &&
                $existing->getExpiry() === -1
            ) {
                if ($existing instanceof InheritanceNode) {
                    $this->removeInheritance($existing);
                }

                unset($this->nodes[$key]);
                $removed = true;
            }
        }

        return $removed;
    }

    public function setTempNode(
        User|Group                 $holder,
        Node                       $node,
        TemporaryNodeMergeStrategy $modifier = TemporaryNodeMergeStrategy::REPLACE
    ): bool {

        $nodeExpiry = $node->getExpiry();

        if ($nodeExpiry === -1) {
            throw new \InvalidArgumentException(
                "Temporary node must have an expiry set"
            );
        }

        $existingNode = null;

        foreach ($this->getOwnNodes() as $existing) {
            if ($existing->getKey() === $node->getKey()) {
                $existingNode = $existing;
                break;
            }
        }

        $builder = $node->toBuilder();

        switch ($modifier) {

            case TemporaryNodeMergeStrategy::ACCUMULATE:
                if ($existingNode && $existingNode->getExpiry() !== -1) {
                    $builder->expiry(
                        $existingNode->getExpiry() +
                        ($nodeExpiry - time())
                    );
                }
                break;

            case TemporaryNodeMergeStrategy::REPLACE:
                if ($existingNode && $existingNode->getExpiry() !== -1) {
                    $builder->expiry(
                        max($nodeExpiry, $existingNode->getExpiry())
                    );
                    $this->unsetNode($existingNode);
                }
                break;

            case TemporaryNodeMergeStrategy::DENY:
                break;
        }

        $this->setNode($builder->build());

        if ($holder instanceof User) {
            $holder->updatePermissions();
        }

        return true;
    }

    public function unsetTempNode(
        User|Group $holder,
        Node       $node,
        ?int       $durationSeconds = null
    ): bool {
        $now = time();
        $changed = false;

        foreach ($holder->getOwnNodes() as $existing) {

            if ($existing->getKey() !== $node->getKey()) {
                continue;
            }

            if ($existing->getExpiry() === -1) {
                continue;
            }

            if ($durationSeconds === null) {
                $holder->unsetNode($existing);
                $changed = true;
                continue;
            }

            $newExpiry = $existing->getExpiry() - $durationSeconds;

            if ($newExpiry <= $now) {
                $holder->unsetNode($existing);
            } else {
                $newNode = $existing->toBuilder()
                    ->expiry($newExpiry)
                    ->build();

                $holder->unsetNode($existing);
                $holder->setNode($newNode);
            }

            $changed = true;
        }

        if ($changed && $holder instanceof User) {
            $holder->updatePermissions();
        }

        return $changed;
    }

    public function findNode(Node|string $nodeInput): ?Node
    {
        $key = $nodeInput instanceof Node ? $nodeInput->getKey() : $nodeInput;

        foreach ($this->getOwnNodes() as $node) {
            if ($node->getKey() === $key) {
                return $node;
            }
        }

        return null;
    }

    private function makeStorageKey(Node $node): string
    {
        if ($node->getExpiry() === -1) {
            return $node->getKey();
        }

        return $node->getKey() . '#temp@' . $node->getExpiry();
    }

    public function auditTemporaryNodes(): bool
    {
        $changed = false;
        $now = time();

        foreach ($this->getOwnNodes() as $node) {
            if ($node->getExpiry() === -1) {
                continue;
            }

            if ($now < $node->getExpiry()) {
                continue;
            }

            $this->unsetNode($node);
            $changed = true;
        }

        return $changed;
    }

    // ==================== Getters & Setters ====================

    /**
     * @return array<string, Node>
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return Node[]
     */
    public function getOwnNodes(): array
    {
        return array_values($this->nodes);
    }

    public function setNodes(array $nodes): void
    {
        $this->nodes = $nodes;
    }

    // ==================== Inheritance Management ====================

    private function addInheritance(InheritanceNode $node): void
    {
        $this->inheritances[] = $node;
    }

    private function removeInheritance(InheritanceNode $targetNode): void
    {
        foreach ($this->inheritances as $index => $node) {
            if ($node === $targetNode) {
                unset($this->inheritances[$index]);
                break;
            }
        }
    }

    /**
     * @return InheritanceNode[]
     */
    public function getInheritances(): array
    {
        return array_values($this->inheritances);
    }

    // ==================== Static Group Update Methods ====================

    public static function updateUsersForGroup(string $changedGroupName): void
    {
        $allUsers = NovaPermsPlugin::getUserManager()->getAllUsers();

        foreach ($allUsers as $user) {
            if (self::userHasGroupInheritance($user, $changedGroupName)) {
                $user->updatePermissions();
            }
        }
    }

    private static function userHasGroupInheritance(User $user, string $groupName): bool
    {
        $checked = [];
        $stack = $user->getGroups();

        while (!empty($stack)) {
            $current = array_pop($stack);

            if ($current === $groupName) return true;
            if (isset($checked[$current])) continue;

            $checked[$current] = true;

            $group = NovaPermsPlugin::getGroupManager()->getIfLoaded($current);
            if ($group !== null) {
                foreach ($group->getInheritances() as $inheritNode) {
                    $stack[] = $inheritNode->getGroup();
                }
            }
        }

        return false;
    }
}