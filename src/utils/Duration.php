<?php

namespace MohamadRZ\NovaPerms\utils;

class Duration
{
    /** @var int|null Duration in seconds; null = infinite */
    private ?int $seconds;

    public function __construct(?int $seconds)
    {
        $this->seconds = $seconds;
    }

    /** ====== STATIC FACTORIES ====== */
    public static function ofSeconds(int $seconds): self { return new self($seconds); }
    public static function ofMinutes(int $minutes): self { return new self($minutes * 60); }
    public static function ofHours(int $hours): self { return new self($hours * 3600); }
    public static function ofDays(int $days): self { return new self($days * 86400); }
    public static function ofWeeks(int $weeks): self { return new self($weeks * 604800); }
    public static function zero(): self { return new self(0); }
    public static function infinite(): self { return new self(null); }

    /** ====== GETTERS ====== */
    public function getSeconds(): ?int { return $this->seconds; }
    public function getMinutes(): ?float { return $this->seconds === null ? null : $this->seconds / 60; }
    public function getHours(): ?float { return $this->seconds === null ? null : $this->seconds / 3600; }
    public function isInfinite(): bool { return $this->seconds === null; }
    public function isZero(): bool { return $this->seconds === 0; }

    /** ====== DURATION COMPARISON ====== */
    /**
     * Returns true if this duration is longer than or equal to the other duration.
     */
    public function longerThan(self $other): bool
    {
        if ($this->isInfinite()) return !$other->isInfinite();
        if ($other->isInfinite()) return false;
        return $this->seconds >= $other->seconds;
    }

    public function shorterThan(self $other): bool
    {
        if ($other->isInfinite()) return !$this->isInfinite();
        if ($this->isInfinite()) return false;
        return $this->seconds < $other->seconds;
    }

    /** ====== EXPIRY CHECKER ====== */
    /**
     * @param int $startTimestamp Unix epoch (seconds)
     * @param int|null $now Default: current time
     * @return bool true if expired OR zero duration, false if still valid OR infinite
     */
    public function isExpired(int $startTimestamp, ?int $now = null): bool
    {
        if ($this->isInfinite()) return false;
        if ($this->isZero()) return true;
        $now ??= time();
        return ($startTimestamp + $this->seconds) <= $now;
    }

    public function getExpiryTimestamp(int $startTimestamp): ?int
    {
        if ($this->isInfinite()) return null;
        return $startTimestamp + $this->seconds;
    }
}
