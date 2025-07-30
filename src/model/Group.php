<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\node\AbstractNode;

class Group extends PermissionHolder
{
    private string $name;

    public function __construct(string $groupName)
    {
        parent::__construct();
        $this->name = $groupName;
        $this->setHolderId($groupName);
        $this->setHolderType('group');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    protected function setHolderId(string $id): void
    {
        $this->holderId = $id;
    }

    protected function setHolderType(string $type): void
    {
        $this->holderType = $type;
    }

    /**
     * @param AbstractNode[] $nodes
     */
    public function importNodes(array $nodes): void
    {
        $this->clearPermissions();
        foreach ($nodes as $node) {
            if ($node instanceof AbstractNode) {
                $this->addPermission($node);
            }
        }
    }
}