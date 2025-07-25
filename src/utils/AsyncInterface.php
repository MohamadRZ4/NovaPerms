<?php

namespace MohamadRZ\StellarRanks\utils;

use MohamadRZ\StellarRanks\StellarRanks;
use pocketmine\scheduler\AsyncTask;

abstract class AsyncInterface
{
    protected StellarRanks $plugin;

    public function __construct(StellarRanks $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Runs async a task that returns a value, then calls $onSuccess($result) or $onError($exception)
     *
     * @param callable $task (Runs in async thread, return your result)
     * @param callable $onSuccess (Runs on main thread, gets result)
     * @param callable|null $onError (Runs on main thread, gets exception or error as string)
     */
    protected function async(callable $task, callable $onSuccess, ?callable $onError = null): void
    {
        $plugin = $this->plugin;

        $class = new class($task, $onSuccess, $onError, $plugin) extends AsyncTask {
            private $task;
            private $onSuccess;
            private $onError;
            private $plugin;
            private $result = null;
            private $error = null;

            public function __construct($task, $onSuccess, $onError, $plugin) {
                $this->task = $task;
                $this->onSuccess = $onSuccess;
                $this->onError = $onError;
                $this->plugin = $plugin;
            }

            public function onRun(): void {
                try {
                    $this->result = ($this->task)();
                } catch (\Throwable $e) {
                    $this->error = $e->getMessage();
                }
            }

            public function onCompletion(): void {
                if ($this->error !== null && $this->onError !== null) {
                    ($this->onError)($this->error);
                } elseif ($this->result !== null) {
                    ($this->onSuccess)($this->result);
                }
            }
        };

        $plugin->getServer()->getAsyncPool()->submitTask($class);
    }
}