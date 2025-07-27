<?php

namespace MohamadRZ\NovaPerms\commands\timing;

use MohamadRZ\NovaPerms\commands\CommandNode;
use MohamadRZ\NovaPerms\timings\Timings;
use pocketmine\utils\TextFormat;

class TimingToggleNode extends CommandNode
{
    private Timings $timing;

    public function __construct(Timings $timing)
    {
        $this->timing = $timing;
    }

    public function getName(): string
    {
        return "toggle";
    }

    public function execute($sender, array $args): void
    {
        if (!$sender->hasPermission("novaperms.timing.toggle")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to toggle timing!");
            return;
        }

        $action = $args[0] ?? '';
        $currentStatus = $this->timing->isEnabled();

        switch (strtolower($action)) {
            case 'on':
            case 'enable':
                if ($currentStatus) {
                    $sender->sendMessage(TextFormat::YELLOW . "Timing is already enabled!");
                } else {
                    $this->timing->setEnabled(true);
                    $sender->sendMessage(TextFormat::GREEN . "✓ Timing has been enabled!");
                    $sender->sendMessage(TextFormat::GRAY . "Performance measurements will now be recorded.");
                }
                break;

            case 'off':
            case 'disable':
                if (!$currentStatus) {
                    $sender->sendMessage(TextFormat::YELLOW . "Timing is already disabled!");
                } else {
                    $this->timing->setEnabled(false);
                    $sender->sendMessage(TextFormat::RED . "✗ Timing has been disabled!");
                    $sender->sendMessage(TextFormat::GRAY . "Performance measurements will no longer be recorded.");
                    $sender->sendMessage(TextFormat::GRAY . "Existing data is still available for viewing and export.");
                }
                break;

            case 'status':
                $this->showStatus($sender);
                break;

            default:
                $newStatus = !$currentStatus;
                $this->timing->setEnabled($newStatus);

                if ($newStatus) {
                    $sender->sendMessage(TextFormat::GREEN . "✓ Timing enabled!");
                } else {
                    $sender->sendMessage(TextFormat::RED . "✗ Timing disabled!");
                }
                $this->showStatus($sender);
                break;
        }
    }

    private function showStatus($sender): void
    {
        $isEnabled = $this->timing->isEnabled();
        $sections = $this->timing->getActiveSections();
        $sectionCount = count($sections);

        $sender->sendMessage(TextFormat::GOLD . "=== Timing Status ===");
        $sender->sendMessage(TextFormat::GRAY . "Status: " . ($isEnabled ?
                TextFormat::GREEN . "Enabled ✓" : TextFormat::RED . "Disabled ✗"));
        $sender->sendMessage(TextFormat::GRAY . "Active Sections: " . TextFormat::WHITE . $sectionCount);

        if ($sectionCount > 0) {
            $totalCalls = 0;
            foreach ($sections as $section) {
                $stats = $this->timing->getStats($section);
                if ($stats !== null) {
                    $totalCalls += $stats['calls'];
                }
            }
            $sender->sendMessage(TextFormat::GRAY . "Total Calls: " . TextFormat::WHITE . number_format($totalCalls));
        }

        $sender->sendMessage("");
        $sender->sendMessage(TextFormat::GRAY . "Commands:");
        $sender->sendMessage(TextFormat::WHITE . "  /sr timing toggle" . TextFormat::GRAY . " - Toggle current state");
        $sender->sendMessage(TextFormat::WHITE . "  /sr timing toggle on/off" . TextFormat::GRAY . " - Set specific state");
        $sender->sendMessage(TextFormat::WHITE . "  /sr timing toggle status" . TextFormat::GRAY . " - Show this status");
    }
}
