<?php

namespace MohamadRZ\NovaPerms\context\providers;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\context\MutableContextSet;

class StaticContextProvider
{
    private array $staticContexts = [];
    private string $configPath;

    public function __construct(string $configPath = null)
    {
        $this->configPath = $configPath . '/contexts.json';
        $this->loadStaticContexts();
    }

    public function getStaticContexts(): ContextSet
    {
        $contextSet = MutableContextSet::create();

        foreach ($this->staticContexts as $key => $values) {
            foreach ($values as $value) {
                $contextSet->add($key, $value);
            }
        }

        return $contextSet->immutableCopy();
    }

    public function getStaticValues(string $key): array
    {
        return $this->staticContexts[strtolower($key)] ?? [];
    }

    public function hasStaticContext(string $key, string $value = null): bool
    {
        $key = strtolower($key);

        if (!isset($this->staticContexts[$key])) {
            return false;
        }

        return $value === null || in_array($value, $this->staticContexts[$key]);
    }

    public function addStaticContext(string $key, string $value): void
    {
        $key = strtolower(trim($key));
        $value = trim($value);

        if (empty($key) || empty($value)) {
            return;
        }

        if (!isset($this->staticContexts[$key])) {
            $this->staticContexts[$key] = [];
        }

        if (!in_array($value, $this->staticContexts[$key])) {
            $this->staticContexts[$key][] = $value;
        }
    }

    public function removeStaticContext(string $key, string $value = null): void
    {
        $key = strtolower(trim($key));

        if ($value === null) {
            unset($this->staticContexts[$key]);
        } else {
            if (isset($this->staticContexts[$key])) {
                $index = array_search($value, $this->staticContexts[$key]);
                if ($index !== false) {
                    unset($this->staticContexts[$key][$index]);
                    $this->staticContexts[$key] = array_values($this->staticContexts[$key]);

                    if (empty($this->staticContexts[$key])) {
                        unset($this->staticContexts[$key]);
                    }
                }
            }
        }
    }

    public function saveToFile(): bool
    {
        try {
            $currentData = [];
            if (file_exists($this->configPath)) {
                $content = file_get_contents($this->configPath);
                $currentData = json_decode($content, true) ?? [];
            }

            $staticContextsForSave = [];
            foreach ($this->staticContexts as $key => $values) {
                $staticContextsForSave[$key] = count($values) === 1 ? $values[0] : $values;
            }

            $currentData['static-contexts'] = $staticContextsForSave;

            $dir = dirname($this->configPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            return file_put_contents(
                    $this->configPath,
                    json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                ) !== false;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function reload(): void
    {
        $this->loadStaticContexts();
    }

    public function getStaticContextKeys(): array
    {
        return array_keys($this->staticContexts);
    }

    public function getRawStaticContexts(): array
    {
        return $this->staticContexts;
    }

    private function loadStaticContexts(): void
    {
        $this->staticContexts = [];

        if (!file_exists($this->configPath)) {
            $this->createDefaultConfig();
            return;
        }

        $content = file_get_contents($this->configPath);
        $data = json_decode($content, true);

        if (!is_array($data) || !isset($data['static-contexts'])) {
            return;
        }

        $staticContextsData = $data['static-contexts'];
        if (is_array($staticContextsData)) {
            foreach ($staticContextsData as $key => $value) {
                $key = strtolower(trim($key));
                if (empty($key)) continue;

                if (is_array($value)) {
                    foreach ($value as $val) {
                        $val = trim($val);
                        if (!empty($val)) {
                            if (!isset($this->staticContexts[$key])) {
                                $this->staticContexts[$key] = [];
                            }
                            $this->staticContexts[$key][] = $val;
                        }
                    }
                } else {
                    $value = trim($value);
                    if (!empty($value)) {
                        $this->staticContexts[$key] = [$value];
                    }
                }
            }
        }
    }

    private function createDefaultConfig(): void
    {
        $defaultConfig = [
            "static-contexts" => [
                "server-type" => "survival"
            ],
            "default-contexts" => []
        ];

        $dir = dirname($this->configPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->configPath,
            json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->staticContexts = ["server-type" => ["survival"]];
    }
}
