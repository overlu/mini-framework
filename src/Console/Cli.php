<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

use Mini\Console\Traits\ReadMessageTrait;
use Mini\Console\Traits\WriteMessageTrait;

class Cli
{
    use ReadMessageTrait, WriteMessageTrait;

    public const LOG_LEVEL2TAG = [
        'info' => 'info',
        'warn' => 'warning',
        'warning' => 'warning',
        'debug' => 'cyan',
        'notice' => 'notice',
        'error' => 'error',
        'critical' => 'critical',
        'alert' => 'alert',
        'emergency' => 'emergency',
    ];

    /*******************************************************************************
     * color render
     ******************************************************************************/

    /**
     * @return Style
     */
    public static function style(): Style
    {
        return Style::instance();
    }

    /**
     * @param string $text
     * @param int|array|string|null $style
     * @return string
     */
    public static function color(string $text, int|array|string $style = null): string
    {
        return Color::render($text, $style);
    }

    /**
     * print log to console
     * @param string $msg
     * @param array $data
     * @param string $type
     * @param array $opts
     *  [
     *  '_category' => 'application',
     *  'process' => 'work',
     *  'pid' => 234,
     *  'coId' => 12,
     *  ]
     */
    public static function clog(string $msg, array $data = [], string $type = 'info', array $opts = []): void
    {
        if (isset(self::LOG_LEVEL2TAG[$type])) {
            $type = ColorTag::add(strtoupper($type), self::LOG_LEVEL2TAG[$type]);
        }
        $userOpts = [];
        foreach ($opts as $n => $v) {
            if (is_numeric($n) || str_starts_with($n, '_')) {
                $userOpts[] = "[$v]";
            } else {
                $userOpts[] = "[$n:$v]";
            }
        }
        $optString = $userOpts ? ' ' . implode(' ', $userOpts) : '';
        $dataString = $data ? PHP_EOL . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : '';

        self::writef("\033[32m[LOG]\033[0m %s %s%s %s %s", date('Y/m/d H:i:s'), $type, $optString, trim($msg), $dataString);
    }

    /*******************************************************************************
     * some helpers
     ******************************************************************************/

    /**
     * @return bool
     */
    public static function supportColor(): bool
    {
        return self::isSupportColor();
    }

    /**
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public static function isSupportColor(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return '10.0.10586' === PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD ||
                false !== getenv('ANSICON') ||
                'ON' === getenv('ConEmuANSI') ||
                'xterm' === getenv('TERM')// || 'cygwin' === getenv('TERM')
                ;
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
    }

    /**
     * @return bool
     */
    public static function isSupport256Color(): bool
    {
        return DIRECTORY_SEPARATOR === '/' && str_contains(getenv('TERM'), '256color');
    }

    /**
     * @return bool
     */
    public static function isAnsiSupport(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return getenv('ANSICON') === true || getenv('ConEmuANSI') === 'ON';
        }

        return true;
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     * @param int|resource $fileDescriptor
     * @return boolean
     */
    public static function isInteractive($fileDescriptor): bool
    {
        return function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }

    /**
     * clear Ansi Code
     * @param string $string
     * @return string
     */
    public static function stripAnsiCode(string $string): string
    {
        return preg_replace('/\033\[[\d;?]*\w/', '', $string);
    }
}
