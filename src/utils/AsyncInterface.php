<?php

namespace MohamadRZ\NovaPerms\utils;

use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\scheduler\ClosureTask;

abstract class AsyncInterface
{
    protected NovaPermsPlugin $plugin;
    private bool $forceSync = false;

    public function __construct(NovaPermsPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function setForceSync(bool $forceSync): void
    {
        $this->forceSync = $forceSync;
    }

    public function isForceSync(): bool
    {
        return $this->forceSync;
    }

    protected function async(callable $task, callable $onSuccess, ?callable $onError = null): void
    {
        $this->runSync($task, $onSuccess, $onError);
    }

    protected function sync(callable $task, callable $onSuccess, ?callable $onError = null): void
    {
        $this->runSync($task, $onSuccess, $onError);
    }

    protected function execute(callable $task)
    {
        try {
            return $task();
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Task execution failed: " . $e->getMessage());
            throw $e;
        }
    }

    protected function executeWithErrorHandling(callable $task, $defaultValue = null)
    {
        try {
            return $task();
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Task execution failed: " . $e->getMessage());
            return $defaultValue;
        }
    }

    protected function scheduleSync(callable $task, int $delay = 1): void
    {
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask($task), $delay);
    }

    private function runSync(callable $task, callable $onSuccess, ?callable $onError = null): void
    {
        try {
            $result = $task();
            $onSuccess($result);
        } catch (\Throwable $e) {
            if ($onError) {
                $onError($e);
            } else {
                $this->plugin->getLogger()->error("Sync task failed: " . $e->getMessage());
            }
        }
    }
}
