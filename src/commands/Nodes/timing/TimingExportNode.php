<?php

namespace MohamadRZ\StellarRanks\commands\timing;

use MohamadRZ\StellarRanks\commands\CommandNode;
use MohamadRZ\StellarRanks\timings\Timings;
use pocketmine\utils\TextFormat;
use Exception;

class TimingExportNode extends CommandNode
{
    private Timings $timing;

    public function __construct(Timings $timing)
    {
        $this->timing = $timing;
    }

    public function getName(): string
    {
        return "export";
    }

    public function execute($sender, array $args): void
    {
        if (!$sender->hasPermission("stellarranks.timing.export")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to export timing reports!");
            return;
        }

        if (!$this->timing->isEnabled()) {
            $sender->sendMessage(TextFormat::RED . "Timing is not enabled!");
        }

        $reason = $args[0] ?? 'manual-command';

        try {
            $sender->sendMessage(TextFormat::YELLOW . "Exporting timing report...");
            $filePath = $this->timing->export($reason);
            $fileName = basename($filePath);

            $sender->sendMessage(TextFormat::GREEN . "✓ Timing report exported successfully!");
            $sender->sendMessage(TextFormat::GRAY . "File: " . TextFormat::WHITE . $fileName);
            $sender->sendMessage(TextFormat::GRAY . "Reason: " . TextFormat::WHITE . $reason);

        } catch (Exception $e) {
            $sender->sendMessage(TextFormat::RED . "✗ Failed to export timing report:");
            $sender->sendMessage(TextFormat::RED . $e->getMessage());
        }
    }
}
