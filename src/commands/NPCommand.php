<?php

namespace MohamadRZ\NovaPerms\commands;

use MohamadRZ\NovaPerms\commands\timing\TimingRootNode;
use MohamadRZ\NovaPerms\commands\verbose\VerboseRootNode;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\verbose\VerboseHandler;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class NPCommand extends Command
{
    private CommandNode $root;
    private VerboseHandler $verboseHandler;

    public function __construct(VerboseHandler $verboseHandler)
    {
        parent::__construct("novaperms", "Manage permissions");
        $this->setAliases(["np", "perm", "perms", "permission", "permissions"]);
        $this->verboseHandler = $verboseHandler;
        $this->root = $this->buildTree();
    }

    protected function buildTree(): CommandNode
    {
        $root = new RootNode();
        $root->registerSubCommand(new UserRootNode());
        $root->registerSubCommand(new GroupRootNode());
        $root->registerSubCommand(new TimingRootNode(NovaPermsPlugin::getTimings()));
        //$root->registerSubCommand(new VerboseRootNode($this->verboseHandler)); i cant add PermissionCheckEvent
        return $root;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $this->root->handle($sender, $args);
    }
}
