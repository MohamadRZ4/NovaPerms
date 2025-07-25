<?php

namespace MohamadRZ\StellarRanks\commands\verbose;

use MohamadRZ\StellarRanks\commands\CommandNode;
use MohamadRZ\StellarRanks\verbose\VerboseHandler;
use pocketmine\utils\TextFormat;

class VerboseRootNode extends CommandNode {

    private VerboseHandler $handler;

    public function __construct(VerboseHandler $handler) {
        $this->handler = $handler;
        $this->registerSubCommands();
    }

    public function getName(): string {
        return "verbose";
    }

    public function execute($sender, array $args): void {
        $sender->sendMessage(TextFormat::GOLD . "Verbose System - Permission Monitoring");
        $sender->sendMessage(TextFormat::YELLOW . "Available commands:");
        $sender->sendMessage(TextFormat::GRAY . "• /sr verbose on [filter] - Start live monitoring");
        $sender->sendMessage(TextFormat::GRAY . "• /sr verbose record [filter] - Start recording mode");
        $sender->sendMessage(TextFormat::GRAY . "• /sr verbose off - Stop current session");
        $sender->sendMessage(TextFormat::GRAY . "• /sr verbose upload - Stop and save session");
        $sender->sendMessage(TextFormat::GRAY . "• /sr verbose command <player> <cmd> - Monitor command execution");
    }

    private function registerSubCommands(): void {
        $this->registerSubCommand(new VerboseOnNode($this->handler));
        $this->registerSubCommand(new VerboseRecordNode($this->handler));
        $this->registerSubCommand(new VerboseOffNode($this->handler));
        $this->registerSubCommand(new VerboseUploadNode($this->handler));
        $this->registerSubCommand(new VerboseCommandNode($this->handler));
    }
}
