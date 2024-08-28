<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Logging;

use Mini\Console\Cli;
use Mini\Facades\Event;
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
class Logger
{
    public static array $cliLevel = [
        'alert' => 'error',
        'critical' => 'error',
        'emergency' => 'error',
        'error' => 'error',
        'info' => 'info',
        'notice' => 'warning',
        'debug' => 'warning',
        'warning' => 'warning'
    ];

    private static array $notOutPutErrorModules = ['system', 'pay', 'crontab'];

    /**
     * @param $name
     * @param $arguments
     */
    public static function __callStatic($name, $arguments)
    {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            $arguments[0] = json_encode($arguments[0], JSON_UNESCAPED_UNICODE);
        } else {
            $arguments = self::parseData($arguments);
        }

        self::output($name, $arguments);
        if (!empty($arguments[1])) {
            $arguments[0] .= PHP_EOL . json_encode($arguments[1], JSON_UNESCAPED_UNICODE);
        }
        if (!empty($arguments[2]) && $arguments[2] === 'system') {
            $arguments[2] = '';
        }
        $arguments[1] = [];
        self::fireLoggerEvent($name, $arguments);
        SeasLog::$name(...$arguments);
    }

    /**
     * @param $name
     * @param $arguments
     */
    private static function output($name, $arguments): void
    {
        if (env('APP_ENV') !== 'production' && (empty($arguments[2]) || !in_array($arguments[2], self::$notOutPutErrorModules, true)) && $types = config('logging.output', false)) {
            $types = $types === true ? 'all' : strtolower($types);
            if ($types !== 'all') {
                $logTypes = explode($types, ',');
            }
            if ($types === 'all' || in_array($name, $logTypes, true)) {
                go(static function () use ($name, $arguments) {
                    Cli::clog($arguments[0], $arguments[1], self::$cliLevel[$name]);
                });
            }
        }
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     * @param string $module
     */
    public static function log($level, $message, array $context = [], string $module = ''): void
    {
        $arguments = self::parseData([
            $message, $context, $module
        ]);
        self::output($level, $arguments);
        self::fireLoggerEvent($level, $arguments);
        SeasLog::log($level, ...$arguments);
    }

    private static function parseData($arguments)
    {
        $arguments[0] = SeasLog::getRequestID() . ': ' . $arguments[0];
        $arguments[1] = $arguments[1] ?? [];
        foreach ($arguments[1] as $key => $value) {
            if ((is_string($value) || is_numeric($value)) && preg_match("/\{" . $key . "\}/", $arguments[0])) {
                $arguments[0] = preg_replace("/\{" . $key . "\}/", (string)$value, $arguments[0]);
                unset($arguments[1][$key]);
            }
        }
        return $arguments;
    }

    private static function fireLoggerEvent(string $level, array $arguments): void
    {
        $config = config('logging');
        if (!empty($config['listen']) && class_exists($config['listen'])) {
            Event::dispatch('logging.' . $level, new LoggingEvent(empty($arguments[1]) ? $arguments[0] : sprintf($arguments[0], $arguments[1]), $arguments[2] ?? '', $level));
        }
    }
}
