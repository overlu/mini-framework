<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

use Mini\Exception\CrontabException;
use Mini\Logging\Log;
use Swoole\Event;
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
    public static function run(): void
    {
        self::checkCrontabRules();
        $enable_crontab_coroutine = config('crontab.enable_crontab_coroutine', true);
        if (version_compare(swoole_version(), '4.6.0') >= 0) {
            if (!$enable_crontab_coroutine) {
                swoole_async_set([
                    'enable_coroutine' => false,
                ]);
            }
        } else {
            Timer::set([
                'enable_coroutine' => (bool)$enable_crontab_coroutine,
            ]);
        }
        self::tick(time() % 60);
        Event::wait();
    }

    public static function checkCrontabRules(): void
    {
        CrontabTaskList::initialTaskList();
        $crontabTaskList = CrontabTaskList::getCrontabTaskList();
        foreach ($crontabTaskList as $crontabTask) {
            if (!Parser::isValid($crontabRule = $crontabTask->rule())) {
                throw new \InvalidArgumentException('Invalid crontab rule: [' . $crontabRule . '] at crontab: ' . get_class($crontabTask));
            }
        }
    }

    /**
     * @param int $after
     */
    private static function tick(int $after = 0): void
    {
        Timer::after((60 - $after) * 1000, function () {
            self::crontabHandle();
            self::$timeId = Timer::tick(60000, function () {
                self::$isInitialed = false;
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
            if (!$task->status()) {
                continue;
            }
            $times = Parser::parse($task->rule());
            $now = time();
            foreach ($times as $time) {
                $time = ($time > $now ? ($time - $now) : 0.001) * 1000;
                Timer::after((int)$time, function () use ($task, $enableCrontabLog) {
                    try {
                        if (!$enableCrontabLog) {
                            $task->handle();
                            return true;
                        }
                        Log::info('[{name}] start.', [
                            'name' => $task->name()
                        ], 'crontab');
                        $response = $task->handle();
                        $response = $response ?? 'null';
                        if (is_array($response) || is_object($response)) {
                            $response = json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                        }
                        Log::info('[{name}] done. response: {response}', [
                            'name' => $task->name(),
                            'response' => (string)$response
                        ], 'crontab');
                        return true;
                    } catch (CrontabException $exception) {
                        Log::error('[{name}] failed. {message} in {file} at line {line}', [
                            'name' => $task->name(),
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