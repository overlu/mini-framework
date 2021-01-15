<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

interface OutputFormatterInterface
{
    /**
     * Sets the decorated flag.
     * @param bool $decorated
     */
    public function setDecorated(bool $decorated);

    /**
     * Gets the decorated flag.
     *
     * @return bool true if the output will decorate messages, false otherwise
     */
    public function isDecorated(): bool;

    /**
     * Sets a new style.
     * @param string $name
     * @param OutputFormatterStyleInterface $style
     */
    public function setStyle(string $name, OutputFormatterStyleInterface $style);

    /**
     * Checks if output formatter has style with specified name.
     *
     * @return bool
     */
    public function hasStyle(string $name): bool;

    /**
     * Gets style options from style with specified name.
     *
     * @param string $name
     * @return OutputFormatterStyleInterface
     *
     */
    public function getStyle(string $name): OutputFormatterStyleInterface;

    /**
     * Formats a message according to the given styles.
     * @param string|null $message
     */
    public function format(?string $message);
}