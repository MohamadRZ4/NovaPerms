<?php

namespace MohamadRZ\NovaPerms\commands;

use pocketmine\utils\TextFormat;

class RootNode extends CommandNode {

    public function getName(): string {
        return "";
    }

    public function execute($sender, array $args): void {
        $sender->sendMessage(TextFormat::GOLD . "NovaPerms - Permission Management System");
        $sender->sendMessage(TextFormat::YELLOW . "Available commands:");

        $children = $this->getChildren();
        foreach ($children as $childName) {
            $sender->sendMessage(TextFormat::GRAY . "• /sr $childName - " . $this->getChildDescription($childName));
        }

        $sender->sendMessage(TextFormat::GRAY . "Use /sr <command> for more information about each command.");
    }

    private function getChildDescription(string $childName): string {
        return match($childName) {
            'verbose' => 'Monitor permission checks in real-time',
            default => 'No description available'
        };
    }
}
