<?php

namespace MohamadRZ\StellarRanks\commands\timing;

use MohamadRZ\StellarRanks\commands\CommandNode;
use MohamadRZ\StellarRanks\timings\Timings;
use pocketmine\utils\TextFormat;

class TimingStatsNode extends CommandNode
{
    private Timings $timing;

    public function __construct(Timings $timing)
    {
        $this->timing = $timing;
    }

    public function getName(): string
    {
        return "stats";
    }

    public function execute($sender, array $args): void
    {
        if (!$sender->hasPermission("stellarranks.timing.stats")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to view timing statistics!");
            return;
        }

        if (empty($args)) {
            $sender->sendMessage(TextFormat::RED . "Usage: /sr timing stats <section>");
            $sender->sendMessage(TextFormat::GRAY . "Use " . TextFormat::WHITE . "/sr timing list" . TextFormat::GRAY . " to see available sections.");
            return;
        }

        $section = $args[0];
        $stats = $this->timing->getStats($section);

        if ($stats === null) {
            $sender->sendMessage(TextFormat::RED . "No timing data found for section: " . TextFormat::WHITE . $section);
            $sender->sendMessage(TextFormat::GRAY . "Use " . TextFormat::WHITE . "/sr timing list" . TextFormat::GRAY . " to see available sections.");
            return;
        }

        // ms
        $yellowThreshold = 50.0; // ms
        $redThreshold = 100.0;

        $avgColor = $stats['average'] > $redThreshold ? TextFormat::RED :
            ($stats['average'] > $yellowThreshold ? TextFormat::YELLOW : TextFormat::GREEN);
        $maxColor = $stats['max'] > $redThreshold ? TextFormat::RED :
            ($stats['max'] > $yellowThreshold ? TextFormat::YELLOW : TextFormat::GREEN);
        $totalColor = $stats['total'] > 1000 ? TextFormat::RED :
            ($stats['total'] > 500 ? TextFormat::YELLOW : TextFormat::WHITE);

        $isSlowAvg = $stats['average'] > $redThreshold;
        $isSlowMax = $stats['max'] > $redThreshold;

        $sender->sendMessage(TextFormat::GOLD . "=== Timing Statistics: " . TextFormat::WHITE . $section . TextFormat::GOLD . " ===");
        $sender->sendMessage(TextFormat::GRAY . "Calls: " . TextFormat::AQUA . number_format($stats['calls']));
        $sender->sendMessage(TextFormat::GRAY . "Total Time: " . $totalColor . number_format($stats['total'], 2) . "ms");
        $sender->sendMessage(TextFormat::GRAY . "Average: " . $avgColor . number_format($stats['average'], 2) . "ms" . ($isSlowAvg ? " !!" : ""));
        $sender->sendMessage(TextFormat::GRAY . "Min Time: " . TextFormat::GREEN . number_format($stats['min'], 2) . "ms");
        $sender->sendMessage(TextFormat::GRAY . "Max Time: " . $maxColor . number_format($stats['max'], 2) . "ms" . ($isSlowMax ? " !!" : ""));
        $sender->sendMessage(TextFormat::GRAY . "Median: " . TextFormat::WHITE . number_format($stats['median'], 2) . "ms");
        $sender->sendMessage(TextFormat::GRAY . "Std Dev: " . TextFormat::WHITE . number_format($stats['stddev'], 2) . "ms");

        if ($stats['calls'] > 0) {
            $callsPerSecond = $stats['calls'] / max(1, $stats['total'] / 1000);
            $sender->sendMessage(TextFormat::GRAY . "Performance: " . TextFormat::AQUA . number_format($callsPerSecond, 1) . " calls/sec");
        }

        if ($isSlowAvg || $isSlowMax) {
            $sender->sendMessage("");
            $sender->sendMessage(TextFormat::YELLOW . "⚠ Performance Warnings:");
            if ($isSlowAvg) {
                $sender->sendMessage(TextFormat::RED . "  • Average execution time is high (>" . $redThreshold . "ms)");
            }
            if ($isSlowMax) {
                $sender->sendMessage(TextFormat::RED . "  • Maximum execution time is very high (>" . $redThreshold . "ms)");
            }
            $sender->sendMessage(TextFormat::GRAY . "  Consider optimizing this section for better performance.");
        } else if ($stats['average'] > $yellowThreshold) {
            $sender->sendMessage(TextFormat::YELLOW . "⚡ This section has moderate execution times.");
        } else {
            $sender->sendMessage(TextFormat::GREEN . "✓ This section performs well!");
        }
    }
}
