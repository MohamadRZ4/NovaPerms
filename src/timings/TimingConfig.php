<?php

namespace MohamadRZ\StellarRanks\timings;

use MohamadRZ\StellarRanks\configs\ConfigManager;

class TimingConfig
{
    private const DEFAULT_BAD_THRESHOLD = 100.0;
    private const DEFAULT_HISTORY_SIZE = 50;

    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function isEnabled(): bool
    {
        return $this->configManager->isTimingEnabled();
    }

    public function setEnabled(bool $enabled): void
    {
        $this->configManager->setTimingEnabled($enabled);
    }

    public function getGlobalBadThreshold(): float
    {
        return $this->configManager->getTimingGlobalBadThreshold();
    }

    public function setGlobalBadThreshold(float $threshold): void
    {
        $this->configManager->setTimingGlobalBadThreshold($threshold);
    }

    public function isAutoResetAfterExport(): bool
    {
        return $this->configManager->isTimingAutoResetAfterExport();
    }

    public function setAutoResetAfterExport(bool $autoReset): void
    {
        $this->configManager->setTimingAutoResetAfterExport($autoReset);
    }

    public function getHistorySize(): int
    {
        return $this->configManager->getTimingHistorySize();
    }

    public function setHistorySize(int $size): void
    {
        $this->configManager->setTimingHistorySize($size);
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'global_bad_threshold' => $this->getGlobalBadThreshold(),
            'auto_reset_after_export' => $this->isAutoResetAfterExport(),
            'history_size' => $this->getHistorySize()
        ];
    }
}