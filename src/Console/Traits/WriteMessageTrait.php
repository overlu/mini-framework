<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console\Traits;

use Mini\Console\Style;

/**
 * Trait WriteMessageTrait
 *
 * @package Toolkit\Cli\Traits
 */
trait WriteMessageTrait
{
    /** @var string */
    private static string $buffer = '';

    /** @var bool */
    private static bool $buffering = false;

    /**
     * @var resource
     */
    private static $outputStream = STDOUT;

    /**
     * @var resource
     */
    private static $errorStream = STDERR;

    /***********************************************************************************
     * Output message
     ***********************************************************************************/

    /**
     * Format and write message to terminal. like printf()
     *
     * @param string $format
     * @param mixed ...$args
     *
     * @return int
     */
    public static function writef(string $format, ...$args): int
    {
        return self::write(sprintf($format, ...$args));
    }

    /**
     * Format and write message to terminal. like printf()
     *
     * @param string $format
     * @param mixed ...$args
     *
     * @return int
     */
    public static function printf(string $format, ...$args): int
    {
        return self::write(sprintf($format, ...$args));
    }

    /**
     * Write raw data to stdout, will disable color render.
     *
     * @param array|string $message
     * @param bool $nl
     * @param bool|int $quit
     * @param array $opts
     *
     * @return int
     */
    public static function writeRaw(array|string $message, bool $nl = true, bool|int $quit = false, array $opts = []): int
    {
        $opts['color'] = false;
        return self::write($message, $nl, $quit, $opts);
    }

    /**
     * Write data to stdout with newline.
     *
     * @param array|string $message
     * @param array $opts
     * @param bool|int $quit
     *
     * @return int
     */
    public static function writeln(array|string $message, bool|int $quit = false, array $opts = []): int
    {
        return self::write($message, true, $quit, $opts);
    }

    /**
     * Write data to stdout with newline.
     *
     * @param array|string $message
     * @param array $opts
     * @param bool|int $quit
     *
     * @return int
     */
    public static function println(array|string $message, bool|int $quit = false, array $opts = []): int
    {
        return self::write($message, true, $quit, $opts);
    }

    /**
     * Write message to stdout.
     *
     * @param array|string $message
     * @param array $opts
     * @param bool|int $quit
     *
     * @return int
     */
    public static function print(array|string $message, bool|int $quit = false, array $opts = []): int
    {
        return self::write($message, false, $quit, $opts);
    }

    /**
     * Write a message to standard output stream.
     *
     * @param array|string $messages Output message
     * @param boolean $nl True Will add line breaks, False Raw output.
     * @param boolean|int $quit If is Int, setting it is exit code.
     *                               'True' translate as code 0 and exit
     *                               'False' will not exit.
     * @param array $opts Some options for write
     *                               [
     *                               'color'  => bool, // whether render color, default is: True.
     *                               'stream' => resource, // the stream resource, default is: STDOUT
     *                               'flush'  => bool, // flush the stream data, default is: True
     *                               ]
     *
     * @return int
     */
    public static function write(array|string|\Stringable $messages, bool $nl = true, bool|int $quit = false, array $opts = []): int
    {
        if (is_array($messages)) {
            $messages = implode($nl ? PHP_EOL : '', $messages);
        }

        $messages = (string)$messages;

        if (!isset($opts['color']) || $opts['color']) {
            $messages = Style::instance()->render($messages);
        } else {
            $messages = Style::stripColor($messages);
        }

        // if open buffering
        if (self::isBuffering()) {
            self::$buffer .= $messages . ($nl ? PHP_EOL : '');

            if (!$quit) {
                return 0;
            }

            $messages = self::$buffer;
            // clear buffer
            self::$buffer = '';
        } else {
            $messages .= $nl ? PHP_EOL : '';
        }

        fwrite($stream = $opts['stream'] ?? self::$outputStream, $messages);

        if (!isset($opts['flush']) || $opts['flush']) {
            fflush($stream);
        }

        // if will quit.
        if ($quit !== false) {
            $code = true === $quit ? 0 : (int)$quit;
            exit($code);
        }

        return 0;
    }

    /**
     * Logs data to stdout
     *
     * @param array|string $text
     * @param bool $nl
     * @param bool|int $quit
     */
    public static function stdout(array|string $text, bool $nl = true, bool|int $quit = false): void
    {
        self::write($text, $nl, $quit);
    }

    /**
     * Logs data to stderr
     *
     * @param array|string $text
     * @param bool $nl
     * @param bool|int $quit
     */
    public static function stderr(array|string $text, bool $nl = true, bool|int $quit = -2): void
    {
        self::write($text, $nl, $quit, [
            'stream' => self::$errorStream,
        ]);
    }

    /***********************************************************************************
     * Output buffer
     ***********************************************************************************/

    /**
     * @return bool
     */
    public static function isBuffering(): bool
    {
        return self::$buffering;
    }

    /**
     * @return string
     */
    public static function getBuffer(): string
    {
        return self::$buffer;
    }

    /**
     * @param string $buffer
     */
    public static function setBuffer(string $buffer): void
    {
        self::$buffer = $buffer;
    }

    /**
     * Start buffering
     */
    public static function startBuffer(): void
    {
        self::$buffering = true;
    }

    /**
     * Clear buffering
     */
    public static function clearBuffer(): void
    {
        self::$buffer = '';
    }

    /**
     * Stop buffering
     *
     * @param bool $flush Whether flush buffer to output stream
     * @param bool $nl Default is False, because the last write() have been added "\n"
     * @param bool $quit
     * @param array $opts
     *
     * @return string If flush = False, will return all buffer text.
     * @see write()
     */
    public static function stopBuffer(bool $flush = true, bool $nl = false, bool $quit = false, array $opts = []): string
    {
        self::$buffering = false;

        if ($flush && self::$buffer) {
            // all text have been rendered by Style::render() in every write();
            $opts['color'] = false;

            // flush to stream
            self::write(self::$buffer, $nl, $quit, $opts);

            // clear buffer
            self::$buffer = '';
        }

        return self::$buffer;
    }

    /**
     * Stop buffering and flush buffer text
     *
     * @param bool $nl
     * @param bool $quit
     * @param array $opts
     *
     * @see write()
     */
    public static function flushBuffer(bool $nl = false, bool $quit = false, array $opts = []): void
    {
        self::stopBuffer(true, $nl, $quit, $opts);
    }

    /**
     * @return resource
     */
    public static function getOutputStream()
    {
        return self::$outputStream;
    }

    /**
     * @param resource $outputStream
     */
    public static function setOutputStream($outputStream): void
    {
        self::$outputStream = $outputStream;
    }

    /**
     * @return resource
     */
    public static function getErrorStream()
    {
        return self::$errorStream;
    }

    /**
     * @param resource $errorStream
     */
    public static function setErrorStream($errorStream): void
    {
        self::$errorStream = $errorStream;
    }
}
