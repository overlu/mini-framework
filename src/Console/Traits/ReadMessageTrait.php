<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console\Traits;

/**
 * Trait ReadMessageTrait
 *
 * @package Toolkit\Cli\Traits
 */
trait ReadMessageTrait
{
    /**
     * @var resource
     */
    private static $inputStream = STDIN;

    /**
     * Read message from STDIN
     *
     * @param mixed $message
     * @param bool $nl
     * @param array $opts
     *
     * @return string
     */
    public static function read($message = null, bool $nl = false, array $opts = []): string
    {
        if ($message) {
            self::write($message, $nl);
        }

        $opts = array_merge([
            'length' => 1024,
            'stream' => self::$inputStream,
        ], $opts);

        return trim(fgets($opts['stream'], $opts['length']));
    }

    /**
     * Gets first character from file pointer
     *
     * @param string $message
     * @param bool $nl
     *
     * @return string
     */
    public static function readChar(string $message = '', bool $nl = false): string
    {
        $line = self::read($message, $nl);

        return $line !== '' ? $line[0] : '';
    }

    /**
     * @return false|resource
     */
    public static function getInputStream()
    {
        return self::$inputStream;
    }

    /**
     * @param resource $inputStream
     */
    public static function setInputStream($inputStream): void
    {
        self::$inputStream = $inputStream;
    }

    /**
     */
    public static function resetInputStream(): void
    {
        self::$inputStream = STDIN;
    }
}
