<?php

namespace MohamadRZ\StellarRanks\storage;

use MohamadRZ\StellarRanks\verbose\session\VerboseSession;

final class VerboseStorage {

    private string $dataPath;

    public function __construct() {
        $this->dataPath = '/verbose/';
        @mkdir($this->dataPath, 0755, true);
    }

    public function store(VerboseSession $session): string {
        $data = $this->serializeSession($session);
        $filename = $session->getId() . '.json';
        $filepath = $this->dataPath . $filename;

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));

        return $filename;
    }

    public function load(string $filename): ?array {
        $filepath = $this->dataPath . $filename;

        if (!file_exists($filepath)) {
            return null;
        }

        $content = file_get_contents($filepath);
        return json_decode($content, true);
    }

    private function serializeSession(VerboseSession $session): array {
        return [
            'id' => $session->getId(),
            'player' => $session->getPlayer()->getName(),
            'mode' => $session->getMode()->value,
            'start_time' => $session->getStartTime(),
            'duration' => $session->getDuration(),
            'entries' => array_map([$this, 'serializeEntry'], $session->getEntries())
        ];
    }

    private function serializeEntry($entry): array {
        return [
            'permission' => $entry->getPermission(),
            'player' => $entry->getPlayerName(),
            'result' => $entry->getResult(),
            'timestamp' => $entry->getTimestamp()
        ];
    }
}
