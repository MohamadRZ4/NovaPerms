<?php

namespace MohamadRZ\StellarRanks\timings;

use InvalidArgumentException;

class TimingProfiler
{
    private const TIME_PRECISION = 3;

    /** @var array<string, float> */
    private array $startTimes = [];

    /** @var array<string, array{times: float[], total: float, calls: int, threshold: float}> */
    private array $sections = [];

    /** @var array<string, array<float>> */
    private array $history = [];

    private TimingConfig $config;

    public function __construct(TimingConfig $config)
    {
        $this->config = $config;
    }

    public function start(string $section, ?float $customThreshold = null): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        if (empty($section)) {
            throw new InvalidArgumentException('Section name cannot be empty');
        }

        $this->startTimes[$section] = hrtime(true) / 1_000_000;

        if (!isset($this->sections[$section])) {
            $this->sections[$section] = [
                'times' => [],
                'total' => 0.0,
                'calls' => 0,
                'threshold' => $customThreshold ?? $this->config->getGlobalBadThreshold()
            ];
            $this->history[$section] = [];
        }
    }

    public function stop(string $section): float
    {
        if (!$this->config->isEnabled()) {
            return 0.0;
        }

        $endTime = hrtime(true) / 1_000_000;

        if (!isset($this->startTimes[$section])) {
            throw new InvalidArgumentException("Section '{$section}' was not started");
        }

        $duration = $endTime - $this->startTimes[$section];
        unset($this->startTimes[$section]);

        $this->sections[$section]['times'][] = $duration;
        $this->sections[$section]['total'] += $duration;
        $this->sections[$section]['calls']++;

        $historySize = $this->config->getHistorySize();
        $this->history[$section][] = $duration;
        if (count($this->history[$section]) > $historySize) {
            array_shift($this->history[$section]);
        }

        return $duration;
    }

    public function reset(): void
    {
        $this->startTimes = [];
        $this->sections = [];
        $this->history = [];
    }

    public function getStats(string $section): ?array
    {
        if (!isset($this->sections[$section])) {
            return null;
        }

        $times = $this->sections[$section]['times'];
        if (empty($times)) {
            return null;
        }

        return [
            'calls' => $this->sections[$section]['calls'],
            'total' => round($this->sections[$section]['total'], self::TIME_PRECISION),
            'average' => round($this->sections[$section]['total'] / $this->sections[$section]['calls'], self::TIME_PRECISION),
            'min' => round(min($times), self::TIME_PRECISION),
            'max' => round(max($times), self::TIME_PRECISION),
            'median' => $this->calculateMedian($times),
            'stddev' => $this->calculateStandardDeviation($times)
        ];
    }

    public function getActiveSections(): array
    {
        return array_keys($this->sections);
    }

    public function getTotalCalls(): int
    {
        return array_sum(array_column($this->sections, 'calls'));
    }

    public function getAllData(): array
    {
        return [
            'sections' => $this->sections,
            'history' => $this->history,
            'total_calls' => $this->getTotalCalls(),
            'total_time' => array_sum(array_column($this->sections, 'total'))
        ];
    }

    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $count = count($values);
        $middle = intval($count / 2);

        if ($count % 2 === 0) {
            $median = ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            $median = $values[$middle];
        }

        return round($median, self::TIME_PRECISION);
    }

    private function calculateStandardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($value) => pow($value - $mean, 2), $values);
        $variance = array_sum($squaredDiffs) / count($values);

        return round(sqrt($variance), self::TIME_PRECISION);
    }
}