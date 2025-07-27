<?php

namespace MohamadRZ\NovaPerms\verbose\filter;

use MohamadRZ\NovaPerms\verbose\data\VerboseEntry;

final class PlayerFilter extends VerboseFilter {

    private string $playerName;

    public function __construct(string $playerName) {
        $this->playerName = strtolower($playerName);
    }

    public function matches(VerboseEntry $entry): bool {
        return strtolower($entry->getPlayerName()) === $this->playerName;
    }
}
