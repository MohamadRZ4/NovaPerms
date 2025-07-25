<?php

namespace MohamadRZ\StellarRanks\commands\timing;

use MohamadRZ\StellarRanks\commands\CommandNode;
use MohamadRZ\StellarRanks\timings\Timings;
use pocketmine\utils\TextFormat;

class TimingListNode extends CommandNode
{
    private Timings $timing;

    public function __construct(Timings $timing)
    {
        $this->timing = $timing;
    }

    public function getName(): string
    {
        return "list";
    }

    public function execute($sender, array $args): void
    {
        if (!$sender->hasPermission("stellarranks.timing.list")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to list timing sections!");
            return;
        }

        if (!$this->timing->isEnabled()) {
            $sender->sendMessage(TextFormat::RED . "Timing is not enabled!");
        }

        $sections = $this->timing->getActiveSections();
        $sortBy = $args[0] ?? 'name';

        if (empty($sections)) {
            $sender->sendMessage(TextFormat::YELLOW . "No timing sections are currently active.");
            $sender->sendMessage(TextFormat::GRAY . "Timing sections will appear here after operations are performed.");
            $sender->sendMessage(TextFormat::GRAY . "Status: " . ($this->timing->isEnabled() ?
                    TextFormat::GREEN . "Enabled" : TextFormat::RED . "Disabled"));
            return;
        }

        $sectionsWithStats = [];
        foreach ($sections as $section) {
            $stats = $this->timing->getStats($section);
            if ($stats !== null) {
                $sectionsWithStats[] = [
                    'name' => $section,
                    'stats' => $stats
                ];
            }
        }

        usort($sectionsWithStats, function($a, $b) use ($sortBy) {
            return match($sortBy) {
                'calls' => $b['stats']['calls'] <=> $a['stats']['calls'],
                'average' => $b['stats']['average'] <=> $a['stats']['average'],
                'total' => $b['stats']['total'] <=> $a['stats']['total'],
                'max' => $b['stats']['max'] <=> $a['stats']['max'],
                default => $a['name'] <=> $b['name'] // name (alphabetical)
            };
        });

        $sender->sendMessage(TextFormat::GOLD . "=== Active Timing Sections ===");
        $sender->sendMessage(TextFormat::GRAY . "Total sections: " . TextFormat::WHITE . count($sections) .
            TextFormat::GRAY . " | Status: " . ($this->timing->isEnabled() ?
                TextFormat::GREEN . "Enabled ✓" : TextFormat::RED . "Disabled ✗"));
        $sender->sendMessage(TextFormat::GRAY . "Sorted by: " . TextFormat::AQUA . $sortBy);
        $sender->sendMessage("");

        $yellowThreshold = 50.0;
        $redThreshold = 100.0;

        foreach ($sectionsWithStats as $index => $data) {
            $section = $data['name'];
            $stats = $data['stats'];

            $avgColor = $stats['average'] > $redThreshold ? TextFormat::RED :
                ($stats['average'] > $yellowThreshold ? TextFormat::YELLOW : TextFormat::GREEN);

            $indicator = $stats['average'] > $redThreshold ? " ⚠" :
                ($stats['average'] > $yellowThreshold ? " ⚡" : " ✓");

            $callsColor = $stats['calls'] > 1000 ? TextFormat::YELLOW : TextFormat::WHITE;
            $totalColor = $stats['total'] > 1000 ? TextFormat::RED :
                ($stats['total'] > 500 ? TextFormat::YELLOW : TextFormat::WHITE);

            $sender->sendMessage(
                TextFormat::GRAY . ($index + 1) . ". " .
                TextFormat::AQUA . $section . $indicator
            );
            $sender->sendMessage(
                TextFormat::GRAY . "   Calls: " . $callsColor . number_format($stats['calls']) .
                TextFormat::GRAY . " | Avg: " . $avgColor . number_format($stats['average'], 2) . "ms" .
                TextFormat::GRAY . " | Total: " . $totalColor . number_format($stats['total'], 2) . "ms"
            );

            // Add spacing every 5 items for readability
            if (($index + 1) % 5 === 0 && $index < count($sectionsWithStats) - 1) {
                $sender->sendMessage("");
            }
        }

        $sender->sendMessage("");
        $sender->sendMessage(TextFormat::GRAY . "Commands:");
        $sender->sendMessage(TextFormat::WHITE . "  /sr timing stats <section>" . TextFormat::GRAY . " - Detailed stats");
        $sender->sendMessage(TextFormat::WHITE . "  /sr timing list [sort]" . TextFormat::GRAY . " - Sort by: name, calls, average, total, max");
        $sender->sendMessage(TextFormat::GRAY . "Legend: " . TextFormat::GREEN . "✓ Good" . TextFormat::GRAY . " | " .
            TextFormat::YELLOW . "⚡ Moderate" . TextFormat::GRAY . " | " .
            TextFormat::RED . "⚠ Slow");
    }
}
