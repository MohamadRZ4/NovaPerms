<?php

namespace MohamadRZ\NovaPerms\verbose\output;

use MohamadRZ\NovaPerms\verbose\data\VerboseEntry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class VerboseOutput {

    public static function sendLiveMessage(Player $player, VerboseEntry $entry): void {
        $color = $entry->getResult() ? TextFormat::GREEN : TextFormat::RED;
        $symbol = $entry->getResult() ? '✓' : '✗';

        $message = sprintf(
            "%s[%s] %s%s %s%s %s-> %s%s",
            TextFormat::GRAY,
            $entry->getFormattedTime(),
            $color,
            $symbol,
            TextFormat::YELLOW,
            $entry->getPlayerName(),
            TextFormat::GRAY,
            TextFormat::WHITE,
            $entry->getPermission()
        );

        $player->sendMessage($message);
    }

    public static function sendCommandResults(Player $player, array $entries): void {
        if (empty($entries)) {
            $player->sendMessage(TextFormat::YELLOW . "No permission checks were recorded during command execution.");
            return;
        }

        $player->sendMessage(TextFormat::GREEN . "Permission checks for command execution:");

        foreach ($entries as $entry) {
            self::sendLiveMessage($player, $entry);
        }

        $player->sendMessage(TextFormat::GRAY . "Total checks: " . count($entries));
    }

    public static function sendSessionSummary(Player $player, array $entries, float $duration): void {
        $total = count($entries);
        $allowed = array_reduce($entries, fn($carry, $entry) => $carry + ($entry->getResult() ? 1 : 0), 0);
        $denied = $total - $allowed;

        $player->sendMessage(TextFormat::GOLD . "Verbose Session Summary:");
        $player->sendMessage(TextFormat::GRAY . "Duration: " . number_format($duration, 2) . "s");
        $player->sendMessage(TextFormat::GRAY . "Total Checks: " . $total);
        $player->sendMessage(TextFormat::GREEN . "Allowed: " . $allowed);
        $player->sendMessage(TextFormat::RED . "Denied: " . $denied);
    }
}
