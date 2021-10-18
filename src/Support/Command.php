<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Exception;
use Mini\Console\Cli;
use Mini\Console\Color;
use Mini\Console\Highlighter;
use Mini\Console\Terminal;
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
     */
    public static function exec(string $shell): ?string
    {
        if (Coroutine::inCoroutine()) {
            $result = System::exec($shell);
            if ($result) {
                return rtrim($result['output']);
            }
        } else {
            $result = shell_exec($shell);
            if ($result) {
                return rtrim($result);
            }
        }
        return '';
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
     * @param string $message
     */
    public static function info(string $message): void
    {
        static::out($message, 'success');
    }

    /**
     * @param string $msg
     */
    public static function infoWithTime(string $msg): void
    {
        static::line(date('Y/m/d H:i:s') . " \033[32m{$msg}\033[0m");
    }

    /**
     * 打印错误消息
     * @param string|\Throwable $message
     */
    public static function error($message): void
    {
        if ($message instanceof \Throwable) {
            static::out(get_class($message), 'error');
            static::out(PHP_EOL);
            static::out("\033[1;37m" . $message->getMessage() . "\033[0m\n");
            static::out("\e[0;1min\e[0m \e[33;4m" . $message->getFile() . ':' . $message->getLine() . "\033[0m");
            static::out(Highlighter::getInstance()->highlightSnippet(file_get_contents($message->getFile()), $message->getLine(), 3, 3));
            static::out($message->getTraceAsString());
        } else {
            static::out($message, 'error');
        }
    }

    /**
     * 打印警告消息
     * @param $message
     */
    public static function warning(string $message): void
    {
        static::out($message, 'warning');
    }

    /**
     * 打印普通消息
     * @param string $message
     * @param bool $newLine
     */
    public static function line(string $message = '', bool $newLine = true): void
    {
//        static::out($message);
        echo Color::render($message . PHP_EOL);
    }

    /**
     * 打印建议消息
     * @param $message
     */
    public static function suggest(string $message): void
    {
        static::out($message, 'suggest');
    }

    /**
     * 输出消息
     * @param string $message
     * @param string|null $style
     * @param bool $newLine
     */
    public static function out(string $message, ?string $style = null, bool $newLine = true): void
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

    /**
     * clear console information
     */
    public static function clear()
    {
        return self::terminal()->clear();
    }

    /**
     * @return Terminal
     */
    public static function terminal(): Terminal
    {
        return Terminal::instance();
    }

    /**
     * @param int $n
     */
    public static function removeLine(int $n = 1): void
    {
        print str_repeat("\r\033[K\033[1A\r\033[K\r", $n);
    }

    /**
     * @param string $message
     * @param array $args
     */
    public static function replace(string $message, array $args = []): void
    {
        foreach ($args as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        if (empty($term_width = static::exec('tput cols'))) {
            $term_width = 64;
        }
        $line_count = 0;
        foreach (explode("\n", $message) as $line) {
            $line_count += count(str_split($line, (int)$term_width));
        }
        static::removeLine($line_count);
        print $message;
    }

    /**
     * @param string $question
     * @param bool $inline
     * @return string
     */
    public static function ask(string $question, bool $inline = true): string
    {
        return Cli::read($question, $inline);
    }
}
