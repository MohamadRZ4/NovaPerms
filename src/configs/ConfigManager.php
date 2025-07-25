<?php

namespace MohamadRZ\StellarRanks\configs;

use pocketmine\utils\Config;

class ConfigManager
{
    private Config $config;
    private string $dataPath;

    public function __construct(string $dataPath)
    {
        $this->dataPath = $dataPath;
        $this->config = new Config($dataPath . "config.yml", Config::YAML, [
            "default_rank" => "guest",
            "primary_key" => PrimaryKeys::USERNAME,
            "timing" => [
                "enabled" => false,
                "global_bad_threshold" => 100.0,
                "auto_reset_after_export" => true,
                "history_size" => 100
            ]
        ]);
    }

    public function getDefaultRank(): string
    {
        return $this->config->get("default_rank");
    }

    public function getPrimaryKey(): PrimaryKeys
    {
        $value = $this->config->get("primary_key");
        return match (strtolower($value)) {
            "uuid" => PrimaryKeys::UUID,
            "ip" => PrimaryKeys::IP,
            "xuid" => PrimaryKeys::XUID,
            default => PrimaryKeys::USERNAME,
        };
    }

    public function setPrimaryKey($value): void
    {
        $this->config->set("primary_key", $value);
        $this->config->save();
    }

    // === TIMING CONFIGURATION METHODS ===

    /**
     * Check if timing system is enabled
     */
    public function isTimingEnabled(): bool
    {
        return $this->config->getNested("timing.enabled", true);
    }

    /**
     * Enable or disable timing system
     */
    public function setTimingEnabled(bool $enabled): void
    {
        $this->config->setNested("timing.enabled", $enabled);
        $this->config->save();
    }

    /**
     * Get global bad threshold for timing (in milliseconds)
     */
    public function getTimingGlobalBadThreshold(): float
    {
        return (float) $this->config->getNested("timing.global_bad_threshold", 100.0);
    }

    /**
     * Set global bad threshold for timing
     */
    public function setTimingGlobalBadThreshold(float $threshold): void
    {
        $this->config->setNested("timing.global_bad_threshold", max(0.0, $threshold));
        $this->config->save();
    }

    /**
     * Check if auto-reset after export is enabled
     */
    public function isTimingAutoResetAfterExport(): bool
    {
        return $this->config->getNested("timing.auto_reset_after_export", true);
    }

    /**
     * Set auto-reset after export option
     */
    public function setTimingAutoResetAfterExport(bool $autoReset): void
    {
        $this->config->setNested("timing.auto_reset_after_export", $autoReset);
        $this->config->save();
    }

    /**
     * Get timing history size
     */
    public function getTimingHistorySize(): int
    {
        return (int) $this->config->getNested("timing.history_size", 50);
    }

    /**
     * Set timing history size
     */
    public function setTimingHistorySize(int $size): void
    {
        $this->config->setNested("timing.history_size", max(10, $size));
        $this->config->save();
    }

    /**
     * Get all timing configuration as array
     */
    public function getTimingConfig(): array
    {
        return $this->config->get("timing", [
            "enabled" => true,
            "global_bad_threshold" => 100.0,
            "auto_reset_after_export" => true,
            "history_size" => 50
        ]);
    }

    /**
     * Update timing configuration from array
     */
    public function updateTimingConfig(array $timingConfig): void
    {
        $current = $this->getTimingConfig();
        $updated = array_merge($current, $timingConfig);

        $this->config->set("timing", $updated);
        $this->config->save();
    }

    /**
     * Reset timing configuration to defaults
     */
    public function resetTimingConfig(): void
    {
        $this->config->set("timing", [
            "enabled" => true,
            "global_bad_threshold" => 100.0,
            "auto_reset_after_export" => true,
            "history_size" => 50
        ]);
        $this->config->save();
    }
}
