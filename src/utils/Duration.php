<?php

namespace MohamadRZ\NovaPerms\utils;

class Duration
{
    public const INFINITE = -1;
    private int $seconds;

    private function __construct(int $seconds)
    {
        $this->seconds = $seconds;
    }

    public static function ofSeconds(int $seconds): self
    {
        return new self($seconds);
    }

    public static function permanent(): self
    {
        return new self(self::INFINITE);
    }

    public static function fromString(string $input): self
    {
        $input = strtolower(trim($input));
        if (in_array($input, ['permanent', 'never', 'infinite'], true)) {
            return self::permanent();
        }

        preg_match_all('/(\d+)([wdhms])/', $input, $matches, PREG_SET_ORDER);
        $seconds = 0;
        foreach ($matches as $match) {
            $number = (int) $match[1];
            switch ($match[2]) {
                case 'w': $seconds += $number * 604800; break;
                case 'd': $seconds += $number * 86400; break;
                case 'h': $seconds += $number * 3600; break;
                case 'm': $seconds += $number * 60; break;
                case 's': $seconds += $number; break;
            }
        }
        return new self($seconds);
    }

    public static function betweenNowAnd(int $timestamp): self
    {
        if ($timestamp <= 0) return new self(0);
        $diff = $timestamp - time();
        return new self($diff > 0 ? $diff : 0);
    }

    public static function fromEndTimestamp(int $end): self
    {
        return self::betweenNowAnd($end);
    }

    public function getSeconds(): int
    {
        return $this->seconds;
    }

    public function format(bool $short = true): string
    {
        if ($this->seconds === self::INFINITE) return "permanent";
        if ($this->seconds <= 0) return $short ? "0s" : "0 seconds";

        $time = $this->seconds;

        $weeks = intdiv($time, 604800); $time %= 604800;
        $days = intdiv($time, 86400);   $time %= 86400;
        $hours = intdiv($time, 3600);   $time %= 3600;
        $minutes = intdiv($time, 60);   $seconds = $time % 60;

        $parts = [];
        if ($weeks > 0)   $parts[] = $weeks . ($short ? 'w' : ' week' . ($weeks > 1 ? 's' : ''));
        if ($days > 0)    $parts[] = $days . ($short ? 'd' : ' day' . ($days > 1 ? 's' : ''));
        if ($hours > 0)   $parts[] = $hours . ($short ? 'h' : ' hour' . ($hours > 1 ? 's' : ''));
        if ($minutes > 0) $parts[] = $minutes . ($short ? 'm' : ' minute' . ($minutes > 1 ? 's' : ''));
        if ($seconds > 0) $parts[] = $seconds . ($short ? 's' : ' second' . ($seconds > 1 ? 's' : ''));

        return implode($short ? ' ' : ', ', $parts);
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public static function builder(): DurationBuilder
    {
        return new DurationBuilder();
    }
}

class DurationBuilder
{
    private int $seconds = 0;
    private bool $permanent = false;

    public function weeks(int $weeks): self { $this->seconds += $weeks * 604800; return $this; }
    public function days(int $days): self   { $this->seconds += $days * 86400;   return $this; }
    public function hours(int $hours): self { $this->seconds += $hours * 3600;   return $this; }
    public function minutes(int $minutes): self { $this->seconds += $minutes * 60; return $this; }
    public function seconds(int $seconds): self { $this->seconds += $seconds;   return $this; }
    public function permanent(): self { $this->permanent = true; return $this; }

    public function build(): Duration
    {
        return $this->permanent ? Duration::permanent() : Duration::ofSeconds($this->seconds);
    }
}
