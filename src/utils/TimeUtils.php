<?php

namespace MohamadRZ\StellarRanks\utils;

use DateTime;
use DateTimeZone;
use DateInterval;
use Exception;

/**
 * StellarUtils class provides utility functions for handling time-related operations.
 * All functions are designed to be robust, reusable, and handle edge cases with proper error handling.
 */
class TimeUtils
{
    /**
     * Get the current timestamp in seconds since Unix epoch.
     *
     * @return int Current timestamp.
     */
    public static function getCurrentTimestamp(): int
    {
        return time();
    }

    /**
     * Get the current date and time in a specified format and timezone.
     *
     * @param string $format Date format (e.g., 'Y-m-d H:i:s').
     * @param string $timezone Timezone name (e.g., 'UTC', 'Asia/Tehran'). Defaults to server's default timezone.
     * @return string Formatted date and time string.
     * @throws Exception If the timezone is invalid.
     */
    public static function getCurrentDateTime(string $format = 'Y-m-d H:i:s', string $timezone = ''): string
    {
        try {
            $date = new DateTime('now', $timezone ? new DateTimeZone($timezone) : null);
            return $date->format($format);
        } catch (Exception $e) {
            throw new Exception("Invalid timezone provided: " . $e->getMessage());
        }
    }

    /**
     * Convert a timestamp to a formatted date string.
     *
     * @param int $timestamp Unix timestamp.
     * @param string $format Date format (e.g., 'Y-m-d H:i:s').
     * @param string $timezone Timezone name (optional).
     * @return string Formatted date string.
     * @throws Exception If the timestamp or timezone is invalid.
     */
    public static function timestampToDate(int $timestamp, string $format = 'Y-m-d H:i:s', string $timezone = ''): string
    {
        try {
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            if ($timezone) {
                $date->setTimezone(new DateTimeZone($timezone));
            }
            return $date->format($format);
        } catch (Exception $e) {
            throw new Exception("Error converting timestamp: " . $e->getMessage());
        }
    }

    /**
     * Convert a date string to a Unix timestamp.
     *
     * @param string $dateString Date string (e.g., '2023-10-15 14:30:00').
     * @param string $timezone Timezone name (optional).
     * @return int Unix timestamp.
     * @throws Exception If the date string or timezone is invalid.
     */
    public static function dateToTimestamp(string $dateString, string $timezone = ''): int
    {
        try {
            $date = new DateTime($dateString, $timezone ? new DateTimeZone($timezone) : null);
            return $date->getTimestamp();
        } catch (Exception $e) {
            throw new Exception("Invalid date string or timezone: " . $e->getMessage());
        }
    }

    /**
     * Calculate the difference between two dates in seconds, minutes, hours, or days.
     *
     * @param string $date1 First date string or 'now'.
     * @param string $date2 Second date string or 'now'.
     * @param string $unit Unit of difference ('seconds', 'minutes', 'hours', 'days').
     * @param string $timezone Timezone name (optional).
     * @return float Difference in the specified unit.
     * @throws Exception If dates or unit are invalid.
     */
    public static function getDateDifference(string $date1, string $date2, string $unit = 'seconds', string $timezone = ''): float
    {
        try {
            $dateTime1 = new DateTime($date1 === 'now' ? 'now' : $date1, $timezone ? new DateTimeZone($timezone) : null);
            $dateTime2 = new DateTime($date2 === 'now' ? 'now' : $date2, $timezone ? new DateTimeZone($timezone) : null);
            $interval = $dateTime1->diff($dateTime2);

            switch (strtolower($unit)) {
                case 'seconds':
                    return abs(($dateTime1->getTimestamp() - $dateTime2->getTimestamp()));
                case 'minutes':
                    return abs(($dateTime1->getTimestamp() - $dateTime2->getTimestamp()) / 60);
                case 'hours':
                    return abs(($dateTime1->getTimestamp() - $dateTime2->getTimestamp()) / 3600);
                case 'days':
                    return abs($interval->days);
                default:
                    throw new Exception("Invalid unit: $unit. Use 'seconds', 'minutes', 'hours', or 'days'.");
            }
        } catch (Exception $e) {
            throw new Exception("Error calculating date difference: " . $e->getMessage());
        }
    }

    /**
     * Add a specified interval to a date.
     *
     * @param string $date Date string or 'now'.
     * @param string $interval Interval to add (e.g., '1 day', '2 hours', '30 minutes').
     * @param string $format Output format (e.g., 'Y-m-d H:i:s').
     * @param string $timezone Timezone name (optional).
     * @return string Formatted date after adding the interval.
     * @throws Exception If the date or interval is invalid.
     */
    public static function addTimeInterval(string $date, string $interval, string $format = 'Y-m-d H:i:s', string $timezone = ''): string
    {
        try {
            $dateTime = new DateTime($date === 'now' ? 'now' : $date, $timezone ? new DateTimeZone($timezone) : null);
            $dateTime->add(DateInterval::createFromDateString($interval));
            return $dateTime->format($format);
        } catch (Exception $e) {
            throw new Exception("Error adding time interval: " . $e->getMessage());
        }
    }

    /**
     * Subtract a specified interval from a date.
     *
     * @param string $date Date string or 'now'.
     * @param string $interval Interval to subtract (e.g., '1 day', '2 hours', '30 minutes').
     * @param string $format Output format (e.g., 'Y-m-d H:i:s').
     * @param string $timezone Timezone name (optional).
     * @return string Formatted date after subtracting the interval.
     * @throws Exception If the date or interval is invalid.
     */
    public static function subtractTimeInterval(string $date, string $interval, string $format = 'Y-m-d H:i:s', string $timezone = ''): string
    {
        try {
            $dateTime = new DateTime($date === 'now' ? 'now' : $date, $timezone ? new DateTimeZone($timezone) : null);
            $dateTime->sub(DateInterval::createFromDateString($interval));
            return $dateTime->format($format);
        } catch (Exception $e) {
            throw new Exception("Error subtracting time interval: " . $e->getMessage());
        }
    }

    /**
     * Check if a given date is in the past.
     *
     * @param string $date Date string to check.
     * @param string $timezone Timezone name (optional).
     * @return bool True if the date is in the past, false otherwise.
     * @throws Exception If the date or timezone is invalid.
     */
    public static function isDateInPast(string $date, string $timezone = ''): bool
    {
        try {
            $dateTime = new DateTime($date, $timezone ? new DateTimeZone($timezone) : null);
            $now = new DateTime('now', $timezone ? new DateTimeZone($timezone) : null);
            return $dateTime < $now;
        } catch (Exception $e) {
            throw new Exception("Error checking if date is in past: " . $e->getMessage());
        }
    }

    /**
     * Check if a given date is in the future.
     *
     * @param string $date Date string to check.
     * @param string $timezone Timezone name (optional).
     * @return bool True if the date is in the future, false otherwise.
     * @throws Exception If the date or timezone is invalid.
     */
    public static function isDateInFuture(string $date, string $timezone = ''): bool
    {
        try {
            $dateTime = new DateTime($date, $timezone ? new DateTimeZone($timezone) : null);
            $now = new DateTime('now', $timezone ? new DateTimeZone($timezone) : null);
            return $dateTime > $now;
        } catch (Exception $e) {
            throw new Exception("Error checking if date is in future: " . $e->getMessage());
        }
    }

    /**
     * Get the start of a time period (day, week, month, year) for a given date.
     *
     * @param string $date Date string or 'now'.
     * @param string $period Period to get start of ('day', 'week', 'month', 'year').
     * @param string $format Output format (e.g., 'Y-m-d H:i:s').
     * @param string $timezone Timezone name (optional).
     * @return string Formatted start of the period.
     * @throws Exception If the date or period is invalid.
     */
    public static function getStartOfPeriod(string $date, string $period, string $format = 'Y-m-d H:i:s', string $timezone = ''): string
    {
        try {
            $dateTime = new DateTime($date === 'now' ? 'now' : $date, $timezone ? new DateTimeZone($timezone) : null);
            switch (strtolower($period)) {
                case 'day':
                    $dateTime->setTime(0, 0, 0);
                    break;
                case 'week':
                    $dateTime->modify('monday this week')->setTime(0, 0, 0);
                    break;
                case 'month':
                    $dateTime->modify('first day of this month')->setTime(0, 0, 0);
                    break;
                case 'year':
                    $dateTime->modify('first day of January this year')->setTime(0, 0, 0);
                    break;
                default:
                    throw new Exception("Invalid period: $period. Use 'day', 'week', 'month', or 'year'.");
            }
            return $dateTime->format($format);
        } catch (Exception $e) {
            throw new Exception("Error getting start of period: " . $e->getMessage());
        }
    }

    /**
     * Get the end of a time period (day, week, month, year) for a given date.
     *
     * @param string $date Date string or 'now'.
     * @param string $period Period to get end of ('day', 'week', 'month', 'year').
     * @param string $format Output format (e.g., 'Y-m-d H:i:s').
     * @param string $timezone Timezone name (optional).
     * @return string Formatted end of the period.
     * @throws Exception If the date or period is invalid.
     */
    public static function getEndOfPeriod(string $date, string $period, string $format = 'Y-m-d H:i:s', string $timezone = ''): string
    {
        try {
            $dateTime = new DateTime($date === 'now' ? 'now' : $date, $timezone ? new DateTimeZone($timezone) : null);
            switch (strtolower($period)) {
                case 'day':
                    $dateTime->setTime(23, 59, 59);
                    break;
                case 'week':
                    $dateTime->modify('sunday this week')->setTime(23, 59, 59);
                    break;
                case 'month':
                    $dateTime->modify('last day of this month')->setTime(23, 59, 59);
                    break;
                case 'year':
                    $dateTime->modify('last day of December this year')->setTime(23, 59, 59);
                    break;
                default:
                    throw new Exception("Invalid period: $period. Use 'day', 'week', 'month', or 'year'.");
            }
            return $dateTime->format($format);
        } catch (Exception $e) {
            throw new Exception("Error getting end of period: " . $e->getMessage());
        }
    }

    /**
     * Convert a time duration to a human-readable format.
     *
     * @param int $seconds Duration in seconds.
     * @return string Human-readable duration (e.g., '2 days, 3 hours, 15 minutes').
     */
    public static function secondsToHumanReadable(int $seconds): string
    {
        if ($seconds < 0) {
            return 'Invalid duration';
        }

        $intervals = [
            'year' => 31536000, // 365 days
            'month' => 2592000, // 30 days
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            'second' => 1,
        ];

        $result = [];
        foreach ($intervals as $unit => $value) {
            if ($seconds >= $value) {
                $count = floor($seconds / $value);
                $seconds -= $count * $value;
                $result[] = $count . ' ' . $unit . ($count > 1 ? 's' : '');
            }
        }

        return !empty($result) ? implode(', ', $result) : '0 seconds';
    }

    /**
     * Get a list of all available timezones.
     *
     * @return array List of timezone identifiers.
     */
    public static function getAvailableTimezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    /**
     * Validate if a date string is in a valid format.
     *
     * @param string $date Date string to validate.
     * @param string $format Expected format (e.g., 'Y-m-d H:i:s').
     * @return bool True if valid, false otherwise.
     */
    public static function isValidDate(string $date, string $format = 'Y-m-d H:i:s'): bool
    {
        try {
            $dateTime = DateTime::createFromFormat($format, $date);
            return $dateTime && $dateTime->format($format) === $date;
        } catch (Exception $e) {
            return false;
        }
    }
}