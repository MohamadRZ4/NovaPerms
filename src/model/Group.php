<?php

namespace MohamadRZ\NovaPerms\model;

use MohamadRZ\NovaPerms\model\PermissionHolder;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\node\Types\WeightNode;
use MohamadRZ\NovaPerms\node\Types\DisplayNameNode;
use MohamadRZ\NovaPerms\node\Types\PrefixNode;
use MohamadRZ\NovaPerms\node\Types\SuffixNode;
use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;
use pocketmine\player\Player;

class Group extends PermissionHolder
{
    private string $name;
    private static ?GroupManager $groupManager = null;

    public function __construct(string $groupName)
    {
        $this->name = $groupName;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
