<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console\Util;

/**
 * Class StrBuffer
 * @package Toolkit\Stdlib\Str
 */
final class StrBuffer implements \Stringable
{
    /**
     * @var string
     */
    private string $body;

    /**
     * @param string $content
     *
     * @return StrBuffer
     */
    public static function new(string $content): StrBuffer
    {
        return new self($content);
    }

    public function __construct(string $content = '')
    {
        $this->body = $content;
    }

    /**
     * @param string $content
     */
    public function write(string $content): void
    {
        $this->body .= $content;
    }

    /**
     * @param string $content
     */
    public function append(string $content): void
    {
        $this->write($content);
    }

    /**
     * @param string $content
     */
    public function prepend(string $content): void
    {
        $this->body = $content . $this->body;
    }

    /**
     * clear data
     */
    public function clear(): string
    {
        $string = $this->body;
        // clear
        $this->body = '';

        return $string;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}