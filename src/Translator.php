<?php

declare(strict_types=1);

namespace MohamadRZ\NovaPerms;

use DirectoryIterator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use RuntimeException;

final class Translator
{
    private const PREFIX = "§7[§l§bN§3P§4§r§7]§r";
    private const LANG_DIR = "lang";

    private static array $messagesByLocale = []; // locale => [dot.key => message]
    private static string $fallbackLocale = "en_us";
    private static array $localePrefixMap = [];

    /**
     * Load language files and prepare locale maps.
     */
    public static function initialize(NovaPermsPlugin $plugin): void
    {
        $dataFolder = $plugin->getDataFolder();
        $languagesPath = $dataFolder . self::LANG_DIR . DIRECTORY_SEPARATOR;

        if (!is_dir($languagesPath) && !mkdir($languagesPath, 0755, true) && !is_dir($languagesPath)) {
            throw new RuntimeException("Could not create lang directory at: $languagesPath");
        }

        $plugin->saveResource(self::LANG_DIR . "/en_us.yml");

        foreach (new DirectoryIterator($languagesPath) as $file) {
            if (!$file->isFile() || $file->getExtension() !== "yml") {
                continue;
            }

            $locale = strtolower(pathinfo($file->getFilename(), PATHINFO_FILENAME));
            $path = $file->getPathname();
            if (!is_readable($path)) {
                continue;
            }

            $config = new Config($path, Config::YAML);
            $all = $config->getAll();
            self::$messagesByLocale[$locale] = self::flattenArray($all);
        }

        $configuredLocale = (string)$plugin->getConfig()->get("default_language", self::$fallbackLocale);
        $configuredLocale = self::normalizeLocaleString($configuredLocale);

        if (!isset(self::$messagesByLocale[$configuredLocale])) {
            throw new RuntimeException("Default locale '$configuredLocale' not found in lang directory.");
        }

        self::$localePrefixMap = [];
        foreach (array_keys(self::$messagesByLocale) as $availableLocale) {
            $prefix = substr($availableLocale, 0, 2);
            if (!isset(self::$localePrefixMap[$prefix])) {
                self::$localePrefixMap[$prefix] = $availableLocale;
            }
        }

        self::$fallbackLocale = $configuredLocale;
    }

    /**
     * Convert a nested array to dot-notated key => value pairs.
     */
    private static function flattenArray(array $data, string $prefix = ""): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $fullKey = $prefix === "" ? (string)$key : ($prefix . "." . $key);
            if (is_array($value)) {
                $result += self::flattenArray($value, $fullKey);
            } elseif (is_scalar($value) || $value === null) {
                $result[$fullKey] = (string)$value;
            }
        }
        return $result;
    }

    /**
     * Normalize locale strings (lowercase, underscores).
     */
    private static function normalizeLocaleString(string $locale): string
    {
        return strtolower(str_replace("-", "_", trim($locale)));
    }

    /**
     * Translate a key and prepend the configured prefix.
     */
    public static function translateWithDefaultPrefix(CommandSender $recipient, string $key, array $variables = []): string
    {
        return self::PREFIX . " " . self::translateForRecipient($recipient, $key, $variables);
    }

    /**
     * Translate a key for the recipient, substituting variables.
     */
    public static function translateForRecipient(CommandSender $recipient, string $key, array $variables = []): string
    {
        $locale = self::resolveLocale($recipient);

        $localeMessages = self::$messagesByLocale[$locale] ?? null;
        $fallbackMessages = self::$messagesByLocale[self::$fallbackLocale] ?? [];

        $message = $localeMessages[$key] ?? $fallbackMessages[$key] ?? $key;

        if ($variables === [] && !str_contains($message, "{")) {
            return $message;
        }

        $normalized = self::prepareVariables($variables);
        $assoc = $normalized["assoc"];
        $positional = $normalized["pos"];

        if ($assoc === [] && $positional === []) {
            return $message;
        }

        if (!preg_match_all("/\{(\w+)}/", $message, $matches)) {
            return $message;
        }

        $placeholders = array_values(array_unique($matches[1]));
        $substitutions = [];
        $positionalIndex = 0;

        foreach ($placeholders as $ph) {
            if (ctype_digit((string)$ph)) {
                $index = (int)$ph;
                $substitutions["{" . $ph . "}"] = $positional[$index] ?? "";
                continue;
            }

            if (array_key_exists($ph, $assoc)) {
                $substitutions["{" . $ph . "}"] = $assoc[$ph];
                continue;
            }

            if (isset($positional[$positionalIndex])) {
                $substitutions["{" . $ph . "}"] = $positional[$positionalIndex];
                $positionalIndex++;
                continue;
            }

            $substitutions["{" . $ph . "}"] = "";
        }

        return $substitutions !== [] ? strtr($message, $substitutions) : $message;
    }

    /**
     * Resolve the best matching locale for the recipient.
     */
    private static function resolveLocale(CommandSender $recipient): string
    {
        $locale = $recipient instanceof Player
            ? self::normalizeLocaleString($recipient->getLocale())
            : self::$fallbackLocale;

        if (isset(self::$messagesByLocale[$locale])) {
            return $locale;
        }

        $short = substr($locale, 0, 2);
        return self::$localePrefixMap[$short] ?? self::$fallbackLocale;
    }

    /**
     * Normalize variables into associative and positional lists.
     */
    private static function prepareVariables(array $variables): array
    {
        $assoc = [];
        $positional = [];

        $convert = static function ($value) use (&$convert) {
            if ($value instanceof CommandSender) {
                return $value->getName();
            }
            if (is_array($value)) {
                return array_map(static fn($v) => $convert($v), $value);
            }
            return $value;
        };

        foreach ($variables as $var) {
            if (is_array($var)) {
                if (array_is_list($var)) {
                    foreach ($var as $item) {
                        $positional[] = $convert($item);
                    }
                } else {
                    foreach ($var as $k => $val) {
                        $converted = $convert($val);
                        $assoc[$k] = $converted;
                        $positional[] = $converted;
                    }
                }
            } else {
                $positional[] = $convert($var);
            }
        }

        return ["assoc" => $assoc, "pos" => $positional];
    }
}