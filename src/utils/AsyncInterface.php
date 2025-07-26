<?php

namespace MohamadRZ\StellarRanks\utils;

use MohamadRZ\StellarRanks\StellarRanks;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\ClosureTask;

abstract class AsyncInterface
{
    protected StellarRanks $plugin;
    private bool $forceSync = false;
    private static array $cache = [];
    private static array $cacheTime = [];

    public function __construct(StellarRanks $plugin)
    {
        $this->plugin = $plugin;
    }

    public function setForceSync(bool $forceSync): void { $this->forceSync = $forceSync; }
    public function isForceSync(): bool { return $this->forceSync; }

    protected function async(callable $task, callable $onSuccess, ?callable $onError = null, ?bool $forceSync = null): void
    {
        ($forceSync ?? $this->forceSync) ? $this->runSync($task, $onSuccess, $onError) : $this->runAsync($task, $onSuccess, $onError);
    }

    protected function sync(callable $task, callable $onSuccess, ?callable $onError = null): void
    {
        $this->runSync($task, $onSuccess, $onError);
    }

    protected function asyncForce(callable $task, callable $onSuccess, ?callable $onError = null): void
    {
        $this->runAsync($task, $onSuccess, $onError);
    }

    protected function execute(callable $task)
    {
        try {
            return $task();
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Sync task failed: " . $e->getMessage());
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

    protected function scheduleRepeating(callable $task, int $period): void
    {
        $this->plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask($task), $period);
    }

    protected function parallel(array $tasks, callable $onAllComplete, ?callable $onError = null): void
    {
        if (empty($tasks)) {
            $onAllComplete([]);
            return;
        }

        $results = [];
        $completed = 0;
        $total = count($tasks);
        $hasError = false;

        foreach ($tasks as $index => $task) {
            $this->runAsync($task,
                function($result) use (&$results, &$completed, $total, $index, $onAllComplete, &$hasError) {
                    if ($hasError) return;
                    $results[$index] = $result;
                    if (++$completed === $total) {
                        ksort($results);
                        $onAllComplete($results);
                    }
                },
                function($error) use ($onError, &$hasError) {
                    if (!$hasError) {
                        $hasError = true;
                        $onError($error);
                    }
                }
            );
        }
    }

    protected function sequence(array $tasks, callable $onAllComplete, ?callable $onError = null): void
    {
        $this->runSequence($tasks, 0, [], $onAllComplete, $onError);
    }

    protected function batch(array $items, callable $taskPerItem, callable $onBatchComplete, ?callable $onError = null, int $batchSize = 10): void
    {
        $batches = array_chunk($items, $batchSize);
        if (empty($batches)) {
            $onBatchComplete([]);
            return;
        }

        $batchResults = [];
        $completedBatches = 0;
        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            $this->runAsync(
                fn() => array_map($taskPerItem, $batch),
                function($results) use (&$batchResults, &$completedBatches, $totalBatches, $batchIndex, $onBatchComplete) {
                    $batchResults[$batchIndex] = $results;
                    if (++$completedBatches === $totalBatches) {
                        ksort($batchResults);
                        $onBatchComplete(array_merge(...$batchResults));
                    }
                },
                $onError
            );
        }
    }

    protected function withTimeout(callable $task, callable $onSuccess, ?callable $onError = null, int $timeoutTicks = 100): void
    {
        $completed = false;

        $this->plugin->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function() use (&$completed, $onError) {
                if (!$completed) {
                    $completed = true;
                    $onError("Task timed out");
                }
            }),
            $timeoutTicks
        );

        $this->runAsync($task,
            function($result) use (&$completed, $onSuccess) {
                if (!$completed) {
                    $completed = true;
                    $onSuccess($result);
                }
            },
            function($error) use (&$completed, $onError) {
                if (!$completed) {
                    $completed = true;
                    $onError($error);
                }
            }
        );
    }

    protected function cached(string $key, callable $task, callable $onSuccess, ?callable $onError = null, int $cacheDuration = 300): void
    {
        $currentTime = time();

        if (isset(self::$cache[$key]) && isset(self::$cacheTime[$key]) &&
            ($currentTime - self::$cacheTime[$key]) < $cacheDuration) {
            $onSuccess(self::$cache[$key]);
            return;
        }

        $this->async($task,
            function($result) use ($key, $onSuccess, $currentTime) {
                self::$cache[$key] = $result;
                self::$cacheTime[$key] = $currentTime;
                $onSuccess($result);
            },
            $onError
        );
    }

    protected function clearCache(string $key): void
    {
        unset(self::$cache[$key], self::$cacheTime[$key]);
    }

    protected function clearAllCache(): void
    {
        self::$cache = [];
        self::$cacheTime = [];
    }

    private function runSync(callable $task, callable $onSuccess, ?callable $onError = null): void
    {
        try {
            $result = $task();
            $this->plugin->getScheduler()->scheduleDelayedTask(
                new ClosureTask(fn() => $onSuccess($result)), 1
            );
        } catch (\Throwable $e) {
            if ($onError) {
                $this->plugin->getScheduler()->scheduleDelayedTask(
                    new ClosureTask(fn() => $onError($e)), 1
                );
            } else {
                $this->plugin->getLogger()->error("Sync task failed: " . $e->getMessage());
            }
        }
    }

    private function runAsync(callable $task, callable $onSuccess, ?callable $onError = null): void
    {
        $this->plugin->getServer()->getAsyncPool()->submitTask(
            new class($task, $onSuccess, $onError, $this->plugin) extends AsyncTask {
                private $task, $onSuccess, $onError, $plugin;
                private $result = null, $error = null, $serializedResult = null;

                public function __construct($task, $onSuccess, $onError, $plugin) {
                    [$this->task, $this->onSuccess, $this->onError, $this->plugin] = [$task, $onSuccess, $onError, $plugin];
                }

                public function onRun(): void {
                    try {
                        $this->result = ($this->task)();
                        if (is_object($this->result) || is_array($this->result)) {
                            $this->serializedResult = serialize($this->result);
                        }
                    } catch (\Throwable $e) {
                        $this->error = $e->getMessage() . "\n" . $e->getTraceAsString();
                    }
                }

                public function onCompletion(): void {
                    try {
                        if ($this->error !== null) {
                            if ($this->onError) {
                                ($this->onError)(new \Exception($this->error));
                            } else {
                                $this->plugin->getLogger()->error("Async task failed: " . $this->error);
                            }
                        } else {
                            $result = $this->serializedResult !== null ? unserialize($this->serializedResult) : $this->result;
                            ($this->onSuccess)($result);
                        }
                    } catch (\Throwable $e) {
                        if ($this->onError) {
                            ($this->onError)($e);
                        } else {
                            $this->plugin->getLogger()->error("Async completion failed: " . $e->getMessage());
                        }
                    }
                }
            }
        );
    }

    private function runSequence(array $tasks, int $index, array $results, callable $onComplete, ?callable $onError): void
    {
        if ($index >= count($tasks)) {
            $onComplete($results);
            return;
        }

        $this->async($tasks[$index],
            function($result) use ($tasks, $index, $results, $onComplete, $onError) {
                $results[$index] = $result;
                $this->runSequence($tasks, $index + 1, $results, $onComplete, $onError);
            },
            $onError
        );
    }
}
