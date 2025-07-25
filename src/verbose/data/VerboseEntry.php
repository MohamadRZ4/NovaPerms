<?php

namespace MohamadRZ\StellarRanks\verbose\data;

final class VerboseEntry {

    private string $permission;
    private string $playerName;
    private bool $result;
    private float $timestamp;

    public function __construct(string $permission, $permissible, bool $result, float $timestamp) {
        $this->permission = $permission;
        $this->playerName = $this->extractPlayerName($permissible);
        $this->result = $result;
        $this->timestamp = $timestamp;
    }

    public function getPermission(): string {
        return $this->permission;
    }

    public function getPlayerName(): string {
        return $this->playerName;
    }

    public function getResult(): bool {
        return $this->result;
    }

    public function getTimestamp(): float {
        return $this->timestamp;
    }

    public function getResultString(): string {
        return $this->result ? 'ALLOW' : 'DENY';
    }

    public function getFormattedTime(): string {
        return date('H:i:s', (int)$this->timestamp) . '.' . str_pad((string)((int)(($this->timestamp - floor($this->timestamp)) * 1000)), 3, '0', STR_PAD_LEFT);
    }

    private function extractPlayerName($permissible): string {
        if (method_exists($permissible, 'getName')) {
            return $permissible->getName();
        }

        return 'Unknown';
    }
}
