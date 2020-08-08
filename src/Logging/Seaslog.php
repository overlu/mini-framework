<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

define('SEASLOG_ALL', 'ALL');
define('SEASLOG_DEBUG', 'DEBUG');
define('SEASLOG_INFO', 'INFO');
define('SEASLOG_NOTICE', 'NOTICE');
define('SEASLOG_WARNING', 'WARNING');
define('SEASLOG_ERROR', 'ERROR');
define('SEASLOG_CRITICAL', 'CRITICAL');
define('SEASLOG_ALERT', 'ALERT');
define('SEASLOG_EMERGENCY', 'EMERGENCY');
define('SEASLOG_DETAIL_ORDER_ASC', 1);
define('SEASLOG_DETAIL_ORDER_DESC', 2);
define('SEASLOG_CLOSE_LOGGER_STREAM_MOD_ALL', 1);
define('SEASLOG_CLOSE_LOGGER_STREAM_MOD_ASSIGN', 2);
define('SEASLOG_REQUEST_VARIABLE_DOMAIN_PORT', 1);
define('SEASLOG_REQUEST_VARIABLE_REQUEST_URI', 2);
define('SEASLOG_REQUEST_VARIABLE_REQUEST_METHOD', 3);
define('SEASLOG_REQUEST_VARIABLE_CLIENT_IP', 4);

class SeasLog
{
    public function __construct()
    {
        #SeasLog init
    }

    public function __destruct()
    {
        #SeasLog destroy
    }

    /**
     * 设置basePath
     *
     * @param $basePath
     *
     * @return bool
     */
    public static function setBasePath($basePath): bool
    {
        return true;
    }

    /**
     * 获取basePath
     *
     * @return string
     */
    public static function getBasePath(): string
    {
        return 'the base path';
    }

    /**
     * 设置本次请求标识
     *
     * @param string
     *
     * @return bool
     */
    public static function setRequestID($request_id): bool
    {
        return true;
    }

    /**
     * 获取本次请求标识
     * @return string
     */
    public static function getRequestID(): string
    {
        return uniqid('', true);
    }

    /**
     * 设置模块目录
     *
     * @param $module
     *
     * @return bool
     */
    public static function setLogger($module): bool
    {
        return true;
    }

    /**
     * 手动清除logger的stream流
     *
     * @param $model
     * @param $logger
     *
     * @return bool
     */
    public static function closeLoggerStream($model = SEASLOG_CLOSE_LOGGER_STREAM_MOD_ALL, $logger): bool
    {
        return true;
    }

    /**
     * 获取最后一次设置的模块目录
     * @return string
     */
    public static function getLastLogger(): string
    {
        return 'the lastLogger';
    }

    /**
     * 设置DatetimeFormat配置
     *
     * @param $format
     *
     * @return bool
     */
    public static function setDatetimeFormat($format): bool
    {
        return true;
    }

    /**
     * 返回当前DatetimeFormat配置格式
     * @return string
     */
    public static function getDatetimeFormat(): string
    {
        return 'the datetimeFormat';
    }

    /**
     * 设置请求变量
     *
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public static function setRequestVariable($key, $value): bool
    {
        return true;
    }

    /**
     * 获取请求变量
     *
     * @param $key
     *
     * @return string
     */
    public static function getRequestVariable($key): string
    {
        return '';
    }

    /**
     * 统计所有类型（或单个类型）行数
     *
     * @param string $level
     * @param string $log_path
     * @param null $key_word
     *
     * @return array
     */
    public static function analyzerCount($level = 'all', $log_path = '*', $key_word = null): array
    {
        return [];
    }

    /**
     * 以数组形式，快速取出某类型log的各行详情
     *
     * @param string $level
     * @param string $log_path
     * @param null $key_word
     * @param int $start
     * @param int $limit
     * @param int $order
     *
     * @return array
     */
    public static function analyzerDetail(
        $level = SEASLOG_INFO,
        $log_path = '*',
        $key_word = null,
        $start = 1,
        $limit = 20,
        $order = SEASLOG_DETAIL_ORDER_ASC
    ): array
    {
        return [];
    }

    /**
     * 获得当前日志buffer中的内容
     *
     * @return array
     */
    public static function getBuffer(): array
    {
        return [];
    }

    /**
     * 获取是否开启buffer
     *
     * @return bool
     */
    public static function getBufferEnabled(): bool
    {
        return true;
    }

    /**
     * 获取当前buffer count
     *
     * @return int
     */
    public static function getBufferCount(): int
    {
        return 0;
    }

    /**
     * 将buffer中的日志立刻刷到硬盘
     *
     * @return bool
     */
    public static function flushBuffer(): bool
    {
        return true;
    }

    /**
     * 记录debug日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function debug($message, array $context = array(), $module = ''): void
    {
        #$level = SEASLOG_DEBUG
    }

    /**
     * 记录info日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function info($message, array $context = array(), $module = ''): void
    {
        #$level = SEASLOG_INFO
    }

    /**
     * 记录notice日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function notice($message, array $context = array(), $module = ''): void
    {
        #$level = SEASLOG_NOTICE
    }

    /**
     * 记录warning日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function warning($message, array $context = array(), $module = ''): void
    {
        #$level = SEASLOG_WARNING
    }

    /**
     * 记录error日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function error($message, array $context = array(), $module = ''): void
    {
        #$level = SEASLOG_ERROR
    }

    /**
     * 记录critical日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function critical($message, array $context = array(), $module = ''): void
    {
        #$level = SEASLOG_CRITICAL
    }

    /**
     * 记录alert日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function alert($message, array $context = array(), $module = ''): void
    {
        #$level = SEASLOG_ALERT
    }

    /**
     * 记录emergency日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function emergency($message, array $context = array(), $module = ''): void
    {
        #$level = SEASLOG_EMERGENCY
    }

    /**
     * 通用日志方法
     *
     * @param $level
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function log($level, $message, array $context = array(), $module = ''): void
    {

    }
}