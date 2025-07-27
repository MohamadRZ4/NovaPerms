<?php

namespace MohamadRZ\NovaPerms\util;

use MohamadRZ\NovaPerms\storage\implementations\file\StorageLocation;

class PlayerMap {

    /** @var array { primaryKey => [username, xuid] } */
    protected array $playerMap = [];
    protected string $directory;
    protected bool $modified = false;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
        $this->load();
    }

    protected function getFilePath(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . StorageLocation::MISC->value . DIRECTORY_SEPARATOR . "player-index.json";
    }

    public function load(): void
    {
        $file = $this->getFilePath();
        if (is_file($file)) {
            $this->playerMap = json_decode(file_get_contents($file), true) ?? [];
        } else {
            $this->playerMap = [];
        }
    }

    public function save(): void
    {
        if (!$this->modified) return;
        $file = $this->getFilePath();
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
        file_put_contents($file, json_encode($this->playerMap, JSON_PRETTY_PRINT));
        $this->modified = false;
    }

    // ---- آپدیت و مدیریت ----
    public function updateMapping(string $username, string $primaryKey, $primaryKeyType): void
    {
        $changed = false;

        if ($primaryKeyType === 'username') {
            if (!isset($this->playerMap[$username]) || $this->playerMap[$username]['xuid'] !== $primaryKey) {
                $this->playerMap[$username] = ['username' => $username, 'xuid' => $primaryKey];
                $changed = true;
            }
        } else {
            if (!isset($this->playerMap[$primaryKey]) || $this->playerMap[$primaryKey]['username'] !== $username) {
                $this->playerMap[$primaryKey] = ['username' => $username, 'xuid' => $primaryKey];
                $changed = true;
            }
        }

        if ($changed) $this->modified = true;
    }

    public function deleteByPrimaryKey(string $primaryKey, $primaryKeyType): void
    {
        if ($primaryKeyType === 'username') {
            unset($this->playerMap[$primaryKey]);
        } else {
            unset($this->playerMap[$primaryKey]);
        }
        $this->modified = true;
    }

    // سرچ و گرفتن مقدارها:
    public function getPlayerName(string $primaryKey, $primaryKeyType): ?string
    {
        if ($primaryKeyType === 'username') return $primaryKey;
        return $this->playerMap[$primaryKey]['username'] ?? null;
    }

    public function getPlayerXuid(string $username, $primaryKeyType): ?string
    {
        if ($primaryKeyType === 'username') return $this->playerMap[$username]['xuid'] ?? null;
        foreach ($this->playerMap as $data) {
            if ($data['username'] === $username) return $data['xuid'] ?? null;
        }
        return null;
    }

    public function getPlayerPrimaryKey(string $identifier, $primaryKeyType): ?string
    {
        if ($primaryKeyType === 'username') {
            return isset($this->playerMap[$identifier]) ? $identifier : null;
        }

        foreach ($this->playerMap as $xuid => $data) {
            if ($data['username'] === $identifier) return $xuid;
        }
        return null;
    }

    public function getAll(): array
    {
        return $this->playerMap;
    }
}
