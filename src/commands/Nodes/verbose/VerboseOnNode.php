<?php

namespace MohamadRZ\NovaPerms\commands\verbose;

use MohamadRZ\NovaPerms\commands\CommandNode;
use MohamadRZ\NovaPerms\verbose\VerboseHandler;
use MohamadRZ\NovaPerms\verbose\VerboseMode;
use MohamadRZ\NovaPerms\verbose\filter\VerboseFilter;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class VerboseOnNode extends CommandNode {

    private VerboseHandler $handler;

    public function __construct(VerboseHandler $handler) {
        $this->handler = $handler;
    }

    public function getName(): string {
        return "on";
    }

    public function execute($sender, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used by players.");
            return;
        }

        $existingSession = $this->handler->getSession($sender);
        if ($existingSession) {
            $sender->sendMessage(TextFormat::YELLOW . "Verbose session already active. Stop it first with '/sr verbose off'");
            return;
        }

        $filter = null;
        if (!empty($args)) {
            $filterExpression = implode(' ', $args);
            try {
                $filter = VerboseFilter::parse($filterExpression);
            } catch (\Exception $e) {
                $sender->sendMessage(TextFormat::RED . "Invalid filter expression: " . $e->getMessage());
                return;
            }
        }

        $this->handler->startSession($sender, VerboseMode::LIVE, $filter);

        $filterText = $filter ? " with filter '" . implode(' ', $args) . "'" : '';
        $sender->sendMessage(TextFormat::GREEN . "Started verbose live monitoring$filterText");
        $sender->sendMessage(TextFormat::GRAY . "Permission checks will appear in chat. Use '/sr verbose off' to stop.");
    }
}
