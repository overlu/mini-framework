<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Exception;
use Mini\Console\Highlighter;
use Swoole\Coroutine\System;

class Command
{
    /**
     * @param string $process 进程名称
     * @return array
     * @throws Exception
     */
    public static function pid(string $process): array
    {
        $process = trim($process);
        $shell = "ps -ef | grep '$process' | grep -v grep | awk '{print $2}'";
        return array_filter(explode(',', str_replace("\n", ',', static::exec($shell))));
    }

    /**
     * 检查supervisor
     * @return bool
     * @throws Exception
     */
    public static function checkSupervisord(): bool
    {
        return static::has('supervisord');
    }

    /**
     * 执行shell脚本
     * @param string $shell
     * @return string|null
     * @throws Exception
     */
    public static function exec(string $shell): ?string
    {
        $result = System::exec($shell);
        if ($result) {
            return $result['output'];
        }
        throw new \RuntimeException('shell command [ ' . $shell . ' ] run failed');
    }

    /**
     * 杀掉进程
     * @param string $process
     * @throws Exception
     */
    public static function kill(string $process): void
    {
        $process = trim($process);
        static::exec("ps -ef | grep '$process' | grep -v grep | awk '{print $2}' | xargs kill -9 2>&1");
    }

    /**
     * 判断进程是否存在
     * @param string $process
     * @return bool
     * @throws Exception
     */
    public static function has(string $process): bool
    {
        return !empty(static::pid($process));
    }

    /**
     * 打印成功消息
     * @param $message
     */
    public static function info($message): void
    {
        static::out($message, 'success');
    }

    public static function infoWithTime($msg): void
    {
        static::line(date('Y/m/d H:i:s') . " \033[32m{$msg}\033[0m");
    }

    /**
     * 打印错误消息
     * @param $message
     */
    public static function error($message): void
    {
        if ($message instanceof \Throwable) {
            static::out(get_class($message), 'error');
            static::line();
            static::line("\033[1;37m" . $message->getMessage() . "\033[0m\n");
            static::line("\e[0;1min\e[0m \e[33;4m" . $message->getFile() . ':' . $message->getLine() . "\033[0m");
            static::line(Highlighter::getInstance()->highlightSnippet(file_get_contents($message->getFile()), $message->getLine(), 3, 3));
            static::line($message->getTraceAsString());
        } else {
            static::out($message, 'error');
        }
    }

    /**
     * 打印警告消息
     * @param $message
     */
    public static function warning($message): void
    {
        static::out($message, 'warning');
    }

    /**
     * 打印普通消息
     * @param $message
     */
    public static function line($message = ''): void
    {
        static::out($message);
    }

    /**
     * 打印建议消息
     * @param $message
     */
    public static function suggest($message): void
    {
        static::out($message, 'suggest');
    }

    /**
     * 输出消息
     * @param $message
     * @param null $style
     * @param bool $newLine
     */
    public static function out($message, $style = null, $newLine = true): void
    {
        $styles = [
            'success' => "\033[0;32m%s\033[0m",
            'error' => "\033[41;37m%s\033[0m",
            'warning' => "\033[43;30m%s\033[0m",
            'suggest' => "\033[44;37m%s\033[0m",
        ];
        $format = $styles[$style] ?? '%s';
        $format .= $newLine ? PHP_EOL : '';
        printf($format, $message);
    }
}
