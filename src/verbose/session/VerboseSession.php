<?php

namespace MohamadRZ\StellarRanks\verbose\session;

use MohamadRZ\StellarRanks\verbose\VerboseMode;
use MohamadRZ\StellarRanks\verbose\filter\VerboseFilter;
use MohamadRZ\StellarRanks\verbose\data\VerboseEntry;
use pocketmine\player\Player;

final class VerboseSession {

    private string $id;
    private Player $player;
    private VerboseMode $mode;
    private ?VerboseFilter $filter;
    private array $entries = [];
    private float $startTime;
    private bool $active = true;

    public function __construct(string $id, Player $player, VerboseMode $mode, ?VerboseFilter $filter = null) {
        $this->id = $id;
        $this->player = $player;
        $this->mode = $mode;
        $this->filter = $filter;
        $this->startTime = microtime(true);
    }

    public function addEntry(VerboseEntry $entry): void {
        if (!$this->active) return;
        $this->entries[] = $entry;
    }

    public function shouldRecord(VerboseEntry $entry): bool {
        if (!$this->active) return false;
        return $this->filter === null || $this->filter->matches($entry);
    }

    public function stop(): void {
        $this->active = false;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getMode(): VerboseMode {
        return $this->mode;
    }

    public function getFilter(): ?VerboseFilter {
        return $this->filter;
    }

    public function getEntries(): array {
        return $this->entries;
    }

    public function getStartTime(): float {
        return $this->startTime;
    }

    public function getDuration(): float {
        return microtime(true) - $this->startTime;
    }

    public function isActive(): bool {
        return $this->active && $this->player->isOnline();
    }

    public function getEntryCount(): int {
        return count($this->entries);
    }
}
