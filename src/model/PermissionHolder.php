<?php

namespace MohamadRZ\NovaPerms\model;


use MohamadRZ\NovaPerms\node\AbstractNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;

abstract class PermissionHolder
{
    protected NodeManager $nodeManager;
    protected string $holderId;
    protected string $holderType;

    public function __construct()
    {
        $this->nodeManager = NovaPermsPlugin::getPermissionManager();
    }

    public function addNode(AbstractNode $permission): void
    {
        $this->nodeManager->addNode($this->holderType, $this->holderId, $permission);
    }

    public function removeNode(AbstractNode $permission): void
    {
        $this->nodeManager->removeNode($this->holderType, $this->holderId, $permission);
    }

    public function getNodes(): array
    {
        return $this->nodeManager->getNodes($this->holderType, $this->holderId);
    }

    public function setNodes(array $permissions): void
    {
        $this->nodeManager->setNodes($this->holderType, $this->holderId, $permissions);
    }

    public function clearNodes(): void
    {
        $this->nodeManager->clearNodes($this->holderType, $this->holderId);
    }

    public function getNodeCount(): int
    {
        return $this->nodeManager->getNodeCount($this->holderType, $this->holderId);
    }

    public function copyNodesFrom(PermissionHolder $other): void
    {
        $permissions = $other->getNodes();
        $this->setNodes($permissions);
    }

    abstract protected function setHolderId(string $id): void;
    abstract protected function setHolderType(string $type): void;
}
