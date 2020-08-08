<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Logging;

use Mini\Console\Cli;
use SeasLog;

/**
 * @method static void alert($message, array $context = [], string $module = '')
 * @method static void critical($message, array $context = [], string $module = '')
 * @method static void debug($message, array $context = [], string $module = '')
 * @method static void emergency($message, array $context = [], string $module = '')
 * @method static void error($message, array $context = [], string $module = '')
 * @method static void info($message, array $context = [], string $module = '')
 * @method static void log($level, $message, array $context = [], string $module = '')
 * @method static void notice($message, array $context = [], string $module = '')
 * @method static void warning($message, array $context = [], string $module = '')
 * @var SeasLog
 */
class Log
{
    public static array $level = [
        'alert' => 'error',
        'critical' => 'error',
        'emergency' => 'error',
        'error' => 'error',
        'info' => 'info',
        'notice' => 'warning',
        'debug' => 'line',
    ];

    public static function __callStatic($name, $arguments)
    {
        go(static function () use ($name, $arguments) {
            if (isset($arguments[0]) && is_array($arguments[0])) {
                $arguments[0] = json_encode($arguments, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            }
            if (config('logging.output', false)) {
                static::output($name, $arguments);
            }
            SeasLog::$name(...$arguments);
        });
    }

    private static function output($name, $arguments): void
    {
        $message = SeasLog::getRequestID() . ': ' . $arguments[0];
        $data = $arguments[1] ?? [];
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        Cli::clog($message, [], $name);
    }
}
