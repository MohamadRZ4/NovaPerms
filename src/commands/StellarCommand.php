<?php

namespace MohamadRZ\StellarRanks\commands;

use MohamadRZ\StellarRanks\commands\timing\TimingRootNode;
use MohamadRZ\StellarRanks\commands\verbose\VerboseRootNode;
use MohamadRZ\StellarRanks\verbose\VerboseHandler;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class StellarCommand extends Command
{
    private CommandNode $root;
    private VerboseHandler $verboseHandler;

    public function __construct(VerboseHandler $verboseHandler)
    {
        parent::__construct("stellar", "Manage permissions");
        $this->setAliases(["sr", "perm", "perms", "permission", "permissions"]);
        $this->verboseHandler = $verboseHandler;
        $this->root = $this->buildTree();
    }

    protected function buildTree(): CommandNode
    {
        $root = new RootNode();
        $root->registerSubCommand(new TimingRootNode());
        //$root->registerSubCommand(new VerboseRootNode($this->verboseHandler)); i cant add PermissionCheckEvent
        return $root;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $this->root->handle($sender, $args);
    }
}
