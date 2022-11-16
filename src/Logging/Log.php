<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Logging;

use JsonException;
use Mini\Console\Cli;
use SeasLog;

/**
 * @method static void alert($message, array $context = [], string $module = '')
 * @method static void critical($message, array $context = [], string $module = '')
 * @method static void debug($message, array $context = [], string $module = '')
 * @method static void emergency($message, array $context = [], string $module = '')
 * @method static void error($message, array $context = [], string $module = '')
 * @method static void info($message, array $context = [], string $module = '')
 * @method static void notice($message, array $context = [], string $module = '')
 * @method static void warning($message, array $context = [], string $module = '')
 * @mixin SeasLog
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
        'debug' => 'warning',
    ];

    /**
     * @param $name
     * @param $arguments
     * @throws JsonException
     */
    public static function __callStatic($name, $arguments)
    {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            $arguments[0] = json_encode($arguments, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        if ((empty($arguments[2]) || $arguments[2] !== 'system') && env('APP_ENV') !== 'production' && $types = config('logging.output', false)) {
            $types = $types === true ? 'all' : strtolower($types);
            if ($types !== 'all') {
                $logTypes = explode($types, ',');
            }
            if ($types === 'all' || in_array($name, $logTypes, true)) {
                go(static function () use ($name, $arguments) {
                    static::output($name, $arguments);
                });
            }
        }
        SeasLog::$name(...$arguments);
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     * @param string $module
     */
    public static function log($level, $message, array $context = [], string $module = ''): void
    {
        SeasLog::log($level, $message, $context, $module);
    }

    /**
     * @param $name
     * @param $arguments
     */
    private static function output($name, $arguments): void
    {
        $message = SeasLog::getRequestID() . ': ' . $arguments[0];
        $data = $arguments[1] ?? [];
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        Cli::clog($message, [], self::$level[$name]);
    }
}
