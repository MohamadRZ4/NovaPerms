<?php

namespace MohamadRZ\StellarRanks\commands\verbose;

use MohamadRZ\StellarRanks\commands\CommandNode;
use MohamadRZ\StellarRanks\verbose\VerboseHandler;
use MohamadRZ\StellarRanks\verbose\output\VerboseOutput;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class VerboseUploadNode extends CommandNode {

    private VerboseHandler $handler;

    public function __construct(VerboseHandler $handler) {
        $this->handler = $handler;
    }

    public function getName(): string {
        return "upload";
    }

    public function execute($sender, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used by players.");
            return;
        }

        $session = $this->handler->getSession($sender);
        if (!$session) {
            $sender->sendMessage(TextFormat::YELLOW . "No active verbose session found.");
            return;
        }

        if (empty($session->getEntries())) {
            $sender->sendMessage(TextFormat::YELLOW . "No permission checks recorded in this session.");
            $this->handler->stopSession($sender);
            return;
        }

        $filename = $this->handler->saveSession($session);
        $this->handler->stopSession($sender);

        $sender->sendMessage(TextFormat::GREEN . "Verbose session saved successfully!");
        $sender->sendMessage(TextFormat::GRAY . "File: " . TextFormat::WHITE . $filename);
        $sender->sendMessage(TextFormat::GRAY . "Location: plugin_data/StellarRanks/verbose/");

        VerboseOutput::sendSessionSummary($sender, $session->getEntries(), $session->getDuration());
    }
}
