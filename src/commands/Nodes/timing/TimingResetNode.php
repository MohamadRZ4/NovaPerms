<?php

namespace MohamadRZ\NovaPerms\commands\timing;

use MohamadRZ\NovaPerms\commands\CommandNode;
use MohamadRZ\NovaPerms\timings\Timings;
use pocketmine\utils\TextFormat;

class TimingResetNode extends CommandNode
{
    private Timings $timing;

    public function __construct(Timings $timing)
    {
        $this->timing = $timing;
    }

    public function getName(): string
    {
        return "reset";
    }

    public function execute($sender, array $args): void
    {
        if (!$sender->hasPermission("novaperms.timing.reset")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to reset timing data!");
            return;
        }

        $confirm = $args[0] ?? '';

        if ($confirm !== 'confirm') {
            $sender->sendMessage(TextFormat::YELLOW . "⚠ This will permanently delete all timing data!");
            $sender->sendMessage(TextFormat::GRAY . "Use " . TextFormat::WHITE . "/sr timing reset confirm" . TextFormat::GRAY . " to confirm.");
            return;
        }

        $sections = $this->timing->getActiveSections();
        $sectionCount = count($sections);

        $this->timing->reset();

        $sender->sendMessage(TextFormat::GREEN . "✓ Timing data has been reset!");
        $sender->sendMessage(TextFormat::GRAY . "Cleared data for " . TextFormat::WHITE . $sectionCount . TextFormat::GRAY . " sections.");
    }
}
