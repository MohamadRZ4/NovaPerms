<?php

namespace MohamadRZ\StellarRanks\commands\verbose;

use MohamadRZ\StellarRanks\commands\CommandNode;
use MohamadRZ\StellarRanks\verbose\VerboseHandler;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class VerboseCommandNode extends CommandNode {

    private VerboseHandler $handler;

    public function __construct(VerboseHandler $handler) {
        $this->handler = $handler;
    }

    public function getName(): string {
        return "command";
    }

    public function execute($sender, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used by players.");
            return;
        }

        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . "Usage: /sr verbose command <player> <command>");
            $sender->sendMessage(TextFormat::GRAY . "Example: /sr verbose command Steve give diamond 64");
            return;
        }

        $targetName = $args[0];
        $command = implode(' ', array_slice($args, 1));

        $sender->sendMessage(TextFormat::YELLOW . "Executing command '$command' as '$targetName' and monitoring permissions...");

        if ($this->handler->executeCommand($sender, $targetName, $command)) {
            $sender->sendMessage(TextFormat::GREEN . "Command executed successfully. Check the output above for permission details.");
        } else {
            $sender->sendMessage(TextFormat::RED . "Failed to execute command. Player '$targetName' might not be online.");
        }
    }
}
