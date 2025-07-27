<?php

namespace MohamadRZ\NovaPerms\verbose;

use MohamadRZ\NovaPerms\verbose\filter\VerboseFilter;
use MohamadRZ\NovaPerms\verbose\output\VerboseOutput;
use MohamadRZ\NovaPerms\verbose\session\VerboseSession;
use MohamadRZ\NovaPerms\verbose\data\VerboseEntry;
use MohamadRZ\NovaPerms\storage\VerboseStorage;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\event\Listener;

final class VerboseHandler implements Listener {

    private array $activeSessions = [];
    private VerboseStorage $storage;

    public function __construct() {
        $this->storage = new VerboseStorage();
    }

    public function startSession(Player $player, VerboseMode $mode, ?VerboseFilter $filter = null): VerboseSession {
        $sessionId = $this->generateSessionId();
        $session = new VerboseSession($sessionId, $player, $mode, $filter);

        $this->activeSessions[$player->getName()] = $session;
        return $session;
    }

    public function stopSession(Player $player): ?VerboseSession {
        $session = $this->activeSessions[$player->getName()] ?? null;
        if ($session) {
            unset($this->activeSessions[$player->getName()]);
        }
        return $session;
    }

    public function getSession(Player $player): ?VerboseSession {
        return $this->activeSessions[$player->getName()] ?? null;
    }

   /* public function onPermissionCheck( $event): void {
        foreach ($this->activeSessions as $session) {
            if (!$session->isActive()) continue;

            $entry = new VerboseEntry(
                $event->getPermission(),
                $event->getPermissible(),
                $event->getResult(),
                microtime(true)
            );

            if ($session->shouldRecord($entry)) {
                $session->addEntry($entry);

                if ($session->getMode() === VerboseMode::LIVE) {
                    VerboseOutput::sendLiveMessage($session->getPlayer(), $entry);
                }
            }
        }
    }*/

    public function executeCommand(Player $executor, string $targetName, string $command): bool {
        $target = $executor->getServer()->getPlayerByPrefix($targetName);
        if (!$target) return false;

        $filter = VerboseFilter::createPlayerFilter($targetName);
        $session = $this->startSession($executor, VerboseMode::COMMAND, $filter);

        $target->getServer()->dispatchCommand($target, $command);

        $this->stopSession($executor);
        VerboseOutput::sendCommandResults($executor, $session->getEntries());

        return true;
    }

    public function saveSession(VerboseSession $session): string {
        return $this->storage->store($session);
    }

    private function generateSessionId(): string {
        return uniqid('verbose_', true);
    }
}
