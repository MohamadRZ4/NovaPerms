<?php

namespace MohamadRZ\StellarRanks\commands\timing;

use MohamadRZ\StellarRanks\commands\CommandNode;
use MohamadRZ\StellarRanks\timings\Timings;
use pocketmine\utils\TextFormat;

class TimingRootNode extends CommandNode
{
    private Timings $timing;

    public function __construct(Timings $timing)
    {
        $this->timing = $timing;
        $this->registerSubCommands();
    }

    private function registerSubCommands(): void
    {
        $this->registerSubCommand(new TimingToggleNode($this->timing));
        $this->registerSubCommand(new TimingExportNode($this->timing));
        $this->registerSubCommand(new TimingResetNode($this->timing));
        $this->registerSubCommand(new TimingStatsNode($this->timing));
        $this->registerSubCommand(new TimingListNode($this->timing));
    }

    public function getName(): string
    {
        return "timing";
    }

    public function execute($sender, array $args): void
    {
        if (!$sender->hasPermission("stellarranks.timing")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to use timing commands!");
            return;
        }

        $isEnabled = $this->timing->isEnabled();
        $sections = $this->timing->getActiveSections();

        $sender->sendMessage(TextFormat::GOLD . "StellarRanks - Timing Management");
        $sender->sendMessage(TextFormat::GRAY . "Status: " . ($isEnabled ?
                TextFormat::GREEN . "Enabled ✓" : TextFormat::RED . "Disabled ✗") .
            TextFormat::GRAY . " | Sections: " . TextFormat::WHITE . count($sections));
        $sender->sendMessage(TextFormat::YELLOW . "Available timing commands:");

        $children = $this->getChildren();
        foreach ($children as $childName) {
            $sender->sendMessage(TextFormat::GRAY . "• /sr timing $childName - " . $this->getChildDescription($childName));
        }

        $sender->sendMessage(TextFormat::GRAY . "Use /sr timing <command> for more information.");
    }

    private function getChildDescription(string $childName): string
    {
        return match($childName) {
            'toggle' => 'Enable/disable timing system',
            'export' => 'Export timing report to file',
            'reset' => 'Reset all timing data',
            'stats' => 'Show statistics for a specific section',
            'list' => 'List all active timing sections',
            default => 'No description available'
        };
    }
}
