<?php

namespace MohamadRZ\NovaPerms\verbose;

enum VerboseMode: string {
    case LIVE = 'live';
    case RECORD = 'record';
    case COMMAND = 'command';

    public function shouldNotifyLive(): bool {
        return $this === self::LIVE;
    }

    public function shouldRecord(): bool {
        return $this !== self::COMMAND;
    }
}
