<?php

namespace MohamadRZ\StellarRanks\commands\verbose;

use MohamadRZ\StellarRanks\commands\CommandNode;
use MohamadRZ\StellarRanks\verbose\VerboseHandler;
use MohamadRZ\StellarRanks\verbose\VerboseMode;
use MohamadRZ\StellarRanks\verbose\filter\VerboseFilter;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class VerboseRecordNode extends CommandNode {

    private VerboseHandler $handler;

    public function __construct(VerboseHandler $handler) {
        $this->handler = $handler;
    }

    public function getName(): string {
        return "record";
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

        $this->handler->startSession($sender, VerboseMode::RECORD, $filter);

        $filterText = $filter ? " with filter '" . implode(' ', $args) . "'" : '';
        $sender->sendMessage(TextFormat::GREEN . "Started verbose recording mode$filterText");
        $sender->sendMessage(TextFormat::GRAY . "Permission checks are being recorded. Use '/sr verbose upload' to save and view results.");
    }
}
