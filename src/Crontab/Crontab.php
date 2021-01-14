<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

use Mini\Exceptions\CrontabException;
use Mini\Logging\Log;
use Swoole\Timer;

class Crontab
{
    /**
     * crontab initiation status
     * @var bool
     */
    public static bool $isInitialed = false;

    /**
     * Timer id
     * @var int
     */
    private static int $timeId = 0;

    /**
     * run crontab
     */
    protected static function run(): void
    {
        $enable_crontab_coroutine = config('crontab.enable_crontab_coroutine', true);
        Timer::set([
            'enable_coroutine' => (bool)$enable_crontab_coroutine,
        ]);
        self::tick(time() % 60);
    }

    /**
     * @param int $after
     */
    private static function tick(int $after = 0): void
    {
        if ($after === 0) {
            self::$timeId = Timer::tick(60000, function () {
                self::crontabHandle();
            });
            return;
        }
        Timer::after((60 - $after) * 1000, function () {
            self::$timeId = Timer::tick(60000, function () {
                self::crontabHandle();
            });
        });
    }

    /**
     * stop crontab
     */
    public static function stop(): void
    {
        if (self::$timeId !== 0) {
            Timer::clear(self::$timeId);
        }
        self::$isInitialed = false;
    }

    /**
     * @return bool
     */
    private static function crontabHandle(): bool
    {
        if (self::$isInitialed) {
            return false;
        }
        self::$isInitialed = true;
        $crontabTaskList = CrontabTaskList::getCrontabTaskList();
        $enableCrontabLog = config('crontab.enable_crontab_log', false);
        foreach ($crontabTaskList as $task) {
            $times = Parser::parse($task->getRule());
            $now = time();
            foreach ($times as $time) {
                Timer::after(($time > $now ? ($time - $now) : 0.001) * 1000, function () use ($task, $enableCrontabLog) {
                    try {
                        if (!$enableCrontabLog) {
                            $task->handle();
                            return true;
                        }
                        Log::info('[:name] start.', [
                            'name' => $task->getCrontabName()
                        ], 'crontab');
                        $response = $task->handle();
                        if (is_array($response) || is_object($response)) {
                            $response = json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                        }
                        Log::info('[:name] done. Response: :response', [
                            'name' => $task->getCrontabName(),
                            'response' => $response
                        ], 'crontab');
                        return true;
                    } catch (CrontabException $exception) {
                        Log::error('[:name] failed. :message in :file at line :line', [
                            'name' => $task->getCrontabName(),
                            'message' => $exception->getMessage(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine()
                        ], 'crontab');
                        return false;
                    }
                });
            }
        }
        return true;
    }
}