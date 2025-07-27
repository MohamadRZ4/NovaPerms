<?php

namespace MohamadRZ\NovaPerms\commands;

abstract class CommandNode
{
    /** @var CommandNode[] */
    protected array $children = [];

    public function registerSubCommand(CommandNode $command): void
    {
        $this->children[strtolower($command->getName())] = $command;
    }

    abstract public function getName(): string;

    abstract public function execute($sender, array $args): void;

    public function handle($sender, array $args): void
    {
        if (isset($args[0]) && isset($this->children[strtolower($args[0])])) {
            $child = $this->children[strtolower($args[0])];
            $child->handle($sender, array_slice($args, 1));
        } else {
            $this->execute($sender, $args);
        }
    }

    public function getChildren(): array
    {
        return array_keys($this->children);
    }
}
