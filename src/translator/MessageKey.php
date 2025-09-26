<?php

namespace MohamadRZ\NovaPerms\translator;

use pocketmine\command\CommandSender;

enum MessageKey: string
{
    case PERM = "perm.perm";
    // ... other keys

    public function send(CommandSender $recipient, mixed...$vars): void
    {
        $recipient->sendMessage(Translator::translateWithDefaultPrefix($recipient, $this->value, $vars));
    }
}