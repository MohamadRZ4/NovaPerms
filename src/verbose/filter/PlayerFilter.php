<?php

namespace MohamadRZ\StellarRanks\verbose\filter;

use MohamadRZ\StellarRanks\verbose\data\VerboseEntry;

final class PlayerFilter extends VerboseFilter {

    private string $playerName;

    public function __construct(string $playerName) {
        $this->playerName = strtolower($playerName);
    }

    public function matches(VerboseEntry $entry): bool {
        return strtolower($entry->getPlayerName()) === $this->playerName;
    }
}
