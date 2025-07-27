<?php

namespace MohamadRZ\NovaPerms\timings;

use MohamadRZ\NovaPerms\configs\ConfigManager;

class Timings
{
    private TimingProfiler $profiler;
    private TimingReporter $reporter;
    private TimingConfig $config;
    private string $dataPath;

    public function __construct(string $dataPath, ConfigManager $configManager)
    {
        $this->dataPath = $dataPath;
        $this->config = new TimingConfig($configManager);
        $this->profiler = new TimingProfiler($this->config);
        $this->reporter = new TimingReporter($dataPath, $this->config);
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function setEnabled(bool $enabled): void
    {
        $this->config->setEnabled($enabled);
    }

    public function start(string $section, ?float $customThreshold = null): void
    {
        $this->profiler->start($section, $customThreshold);
    }

    public function stop(string $section): float
    {
        return $this->profiler->stop($section);
    }

    public function export(string $reason = ''): string
    {
        $data = $this->profiler->getAllData();
        $filePath = $this->reporter->generateReport($data, $reason);

        if ($this->config->isAutoResetAfterExport()) {
            $this->reset();
        }

        return $filePath;
    }

    public function reset(): void
    {
        $this->profiler->reset();
    }

    public function getStats(string $section): ?array
    {
        return $this->profiler->getStats($section);
    }

    public function getActiveSections(): array
    {
        return $this->profiler->getActiveSections();
    }

    public function getConfig(): array
    {
        return array_merge($this->config->toArray(), [
            'active_sections' => count($this->profiler->getActiveSections()),
            'total_calls' => $this->profiler->getTotalCalls(),
            'data_path' => $this->dataPath
        ]);
    }

    // Configuration methods
    public function setGlobalBadThreshold(float $threshold): void
    {
        $this->config->setGlobalBadThreshold($threshold);
    }

    public function getGlobalBadThreshold(): float
    {
        return $this->config->getGlobalBadThreshold();
    }

    public function setAutoResetAfterExport(bool $autoReset): void
    {
        $this->config->setAutoResetAfterExport($autoReset);
    }

    public function isAutoResetAfterExport(): bool
    {
        return $this->config->isAutoResetAfterExport();
    }
}