<?php

namespace MohamadRZ\StellarRanks\timings;

use RuntimeException;

class TimingReporter
{
    private const TIME_PRECISION = 3;

    private string $dataPath;
    private TimingConfig $config;

    public function __construct(string $dataPath, TimingConfig $config)
    {
        $this->dataPath = rtrim($dataPath, '/\\') . DIRECTORY_SEPARATOR;
        $this->config = $config;
    }

    public function generateReport(array $data, string $reason = ''): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $reasonSuffix = $reason ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $reason) : '';
        $filename = "timing_report_{$timestamp}{$reasonSuffix}.log";

        $timingsDir = $this->dataPath . 'timings' . DIRECTORY_SEPARATOR;
        $this->ensureDirectoryExists($timingsDir);

        $filepath = $timingsDir . $filename;
        $content = $this->buildReportContent($data, $reason);

        if (file_put_contents($filepath, $content, LOCK_EX) === false) {
            throw new RuntimeException("Failed to write timing report to: {$filepath}");
        }

        return $filepath;
    }

    private function buildReportContent(array $data, string $reason): string
    {
        $report = [];
        $sections = $data['sections'];
        $history = $data['history'];
        $totalCalls = $data['total_calls'];
        $totalTime = $data['total_time'];

        $report[] = str_repeat('=', 80);
        $report[] = '[STELLAR] TIMING PERFORMANCE REPORT';
        $report[] = str_repeat('=', 80);
        $report[] = 'Generated: ' . date('Y-m-d H:i:s T');

        if ($reason) {
            $report[] = 'Reason: ' . $reason;
        }

        $report[] = 'Plugin: StellarRanks';
        $report[] = 'Timing Status: ' . ($this->config->isEnabled() ? 'ENABLED' : 'DISABLED');
        $report[] = 'Global Threshold: ' . $this->config->getGlobalBadThreshold() . 'ms';
        $report[] = 'Auto Reset: ' . ($this->config->isAutoResetAfterExport() ? 'YES' : 'NO');
        $report[] = 'History Size: ' . $this->config->getHistorySize();
        $report[] = str_repeat('=', 80);
        $report[] = '';

        if (empty($sections)) {
            $report[] = '[WARNING] No timing data available';
            if (!$this->config->isEnabled()) {
                $report[] = '[INFO] Timing system is currently disabled';
            }
            return implode(PHP_EOL, $report);
        }

        $slowSections = [];
        $goodSections = [];

        foreach ($sections as $name => $data) {
            if ($data['calls'] > 0) {
                $avgTime = $data['total'] / $data['calls'];
                if ($avgTime > $data['threshold']) {
                    $slowSections[] = $name;
                } else {
                    $goodSections[] = $name;
                }
            }
        }

        $report[] = 'SUMMARY:';
        $report[] = '  Total Sections: ' . count($sections);
        $report[] = '  Total Calls: ' . number_format($totalCalls);
        $report[] = '  Total Time: ' . round($totalTime, self::TIME_PRECISION) . 'ms';
        $report[] = '  Average per Call: ' . ($totalCalls > 0 ? round($totalTime / $totalCalls, self::TIME_PRECISION) : 0) . 'ms';
        $report[] = '  Slow Sections: ' . count($slowSections);
        $report[] = '  Good Sections: ' . count($goodSections);
        $report[] = '';

        if (!empty($slowSections)) {
            $report[] = '[ALERT] SLOW SECTIONS (Above Threshold):';
            foreach ($slowSections as $section) {
                $sectionData = $sections[$section];
                $avgTime = $sectionData['total'] / $sectionData['calls'];
                $report[] = "  - {$section}: {$avgTime}ms avg (threshold: {$sectionData['threshold']}ms)";
            }
            $report[] = '';
        }

        $report[] = 'DETAILED ANALYSIS:';
        $report[] = str_repeat('-', 80);

        foreach ($sections as $sectionName => $sectionData) {
            if ($sectionData['calls'] === 0) {
                continue;
            }

            $report = array_merge($report, $this->generateSectionReport($sectionName, $sectionData, $history[$sectionName] ?? []));
        }

        $report[] = 'MEMORY USAGE:';
        $report[] = '  PHP Memory: ' . $this->formatBytes(memory_get_usage(true));
        $report[] = '  Peak Memory: ' . $this->formatBytes(memory_get_peak_usage(true));
        $report[] = '  Timing Data Size: ~' . $this->formatBytes($this->estimateDataSize($sections, $history));
        $report[] = '';

        $report[] = 'RECOMMENDATIONS:';
        if (!empty($slowSections)) {
            $report[] = "  - Investigate slow sections: " . implode(', ', $slowSections);
            $report[] = "  - Consider optimizing database queries or caching";
            $report[] = "  - Review algorithm complexity in slow sections";
        } else {
            $report[] = "  - All sections are performing within acceptable thresholds";
        }

        if ($totalCalls > 10000) {
            $report[] = "  - High call count detected. Consider reducing frequency if possible";
        }

        $report[] = "  - Regular monitoring recommended for performance tracking";
        $report[] = '';

        $report[] = str_repeat('=', 80);
        $report[] = 'End of Report';
        $report[] = str_repeat('=', 80);

        return implode(PHP_EOL, $report);
    }

    private function generateSectionReport(string $sectionName, array $data, array $history): array
    {
        $times = $data['times'];
        $average = $data['total'] / $data['calls'];
        $min = min($times);
        $max = max($times);
        $median = $this->calculateMedian($times);
        $stddev = $this->calculateStandardDeviation($times);

        $status = $average > $data['threshold'] ? '[SLOW]' : '[OK]';
        $report = [];

        $report[] = "Section: {$sectionName} {$status}";
        $report[] = "  Calls: " . number_format($data['calls']);
        $report[] = "  Total: " . round($data['total'], self::TIME_PRECISION) . "ms";
        $report[] = "  Average: " . round($average, self::TIME_PRECISION) . "ms";
        $report[] = "  Min: " . round($min, self::TIME_PRECISION) . "ms";
        $report[] = "  Max: " . round($max, self::TIME_PRECISION) . "ms";
        $report[] = "  Median: " . round($median, self::TIME_PRECISION) . "ms";
        $report[] = "  Std Dev: " . round($stddev, self::TIME_PRECISION) . "ms";
        $report[] = "  Threshold: " . $data['threshold'] . "ms";

        $grade = $this->calculatePerformanceGrade($average, $data['threshold']);
        $report[] = "  Grade: {$grade}";

        if (!empty($history)) {
            $recentTimes = array_slice($history, -10);
            $recentAvg = array_sum($recentTimes) / count($recentTimes);
            $trend = $this->analyzeTrend($recentTimes);
            $report[] = "  Recent Avg (last " . count($recentTimes) . "): " . round($recentAvg, self::TIME_PRECISION) . "ms";
            $report[] = "  Trend: {$trend}";
        }

        $report[] = '';
        return $report;
    }

    private function calculateMedian(array $values): float
    {
        if (empty($values)) return 0.0;
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
        if (count($values) < 2) return 0.0;
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($value) => pow($value - $mean, 2), $values);
        $variance = array_sum($squaredDiffs) / count($values);
        return round(sqrt($variance), self::TIME_PRECISION);
    }

    private function calculatePerformanceGrade(float $averageTime, float $threshold): string
    {
        $ratio = $averageTime / $threshold;
        if ($ratio <= 0.25) return 'A+';
        if ($ratio <= 0.5) return 'A';
        if ($ratio <= 0.75) return 'B';
        if ($ratio <= 1.0) return 'C';
        if ($ratio <= 1.5) return 'D';
        return 'F';
    }

    private function analyzeTrend(array $recentTimes): string
    {
        if (count($recentTimes) < 3) return 'Insufficient data';

        $firstHalf = array_slice($recentTimes, 0, intval(count($recentTimes) / 2));
        $secondHalf = array_slice($recentTimes, intval(count($recentTimes) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $percentChange = ($firstAvg > 0) ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;

        if (abs($percentChange) < 5) return 'Stable';
        elseif ($percentChange < 0) return 'Improving (' . round(abs($percentChange), 1) . '% faster)';
        else return 'Degrading (' . round($percentChange, 1) . '% slower)';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    private function estimateDataSize(array $sections, array $history): int
    {
        $size = 0;
        foreach ($sections as $name => $data) {
            $size += strlen($name) * 2 + count($data['times']) * 8 + 32;
        }
        foreach ($history as $name => $times) {
            $size += strlen($name) * 2 + count($times) * 8;
        }
        return $size;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new RuntimeException("Failed to create directory: {$directory}");
            }
        }
    }
}