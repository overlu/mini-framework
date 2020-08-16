<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

interface OutputFormatterStyleInterface
{
    /**
     * Sets style foreground color.
     * @param string|null $color
     */
    public function setForeground(?string $color = null);

    /**
     * Sets style background color.
     * @param string|null $color
     */
    public function setBackground(?string $color = null);

    /**
     * Sets some specific style option.
     * @param string $option
     */
    public function setOption(string $option);

    /**
     * Unsets some specific style option.
     * @param string $option
     */
    public function unsetOption(string $option);

    /**
     * Sets multiple style options at once.
     * @param array $options
     */
    public function setOptions(array $options);

    /**
     * Applies the style to a given text.
     *
     * @param string $text
     * @return string
     */
    public function apply(string $text): string;
}