<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

use Exception;
use InvalidArgumentException;
use Mini\Bootstrap;
use Mini\Logging\Logger;
use Swoole\Event;
use Swoole\Timer;

class Crontab
{
    /**
     * crontab's initiation status
     * @var bool
     */
    public static bool $isInitialed = false;

    /**
     * Timer id
     * @var int
     */
    private static int $timeId = 0;

    /**
     * The array of filter callbacks.
     *
     * @var array
     */
    protected array $filters = [];

    /**
     * The array of reject callbacks.
     *
     * @var array
     */
    protected array $rejects = [];

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
                throw new InvalidArgumentException('Invalid crontab rule: [' . $crontabRule . '] at crontab: ' . get_class($crontabTask));
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
     * @return void
     */
    private static function crontabHandle(): void
    {
        if (self::$isInitialed) {
            return;
        }
        self::$isInitialed = true;
        $crontabTaskList = CrontabTaskList::getCrontabTaskList();
        $enableCrontabLog = config('crontab.enable_crontab_log', false);
        foreach ($crontabTaskList as $task) {
//            if (!$task->status()) {
//                continue;
//            }
            $times = Parser::parse($task->rule());
            $now = time();
            foreach ($times as $time) {
                $time = ($time > $now ? ($time - $now) : 0.001) * 1000;
                if (!$task->status()) {
                    continue;
                }
                Timer::after((int)$time, function () use ($task, $enableCrontabLog) {
                    try {
                        if (!$enableCrontabLog) {
                            $response = $task->handle();
                            if (method_exists($task, 'success')) {
                                $task->success($response);
                            }
                            return;
                        }
                        Logger::info('[{name}] start.', [
                            'name' => $task->name()
                        ], 'crontab');
                        $response = $task->handle();
                        if (method_exists($task, 'success')) {
                            $task->success($response);
                        }
                        if (is_array($response) || is_object($response)) {
                            $response = json_encode($response, JSON_UNESCAPED_UNICODE);
                        }
                        Logger::info('[{name}] done. response: {response}', [
                            'name' => $task->name(),
                            'response' => (string)$response
                        ], 'crontab');
                    } catch (Exception $exception) {
                        if (method_exists($task, 'fail')) {
                            $task->fail($exception);
                        }
                        Logger::error('[{name}] failed. {message} in {file} at line {line}', [
                            'name' => $task->name(),
                            'message' => $exception->getMessage(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine()
                        ], 'crontab');
                    }
                });
            }
        }
    }
}
