<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Console;

interface CommandInterface
{
    /**
     * @return mixed
     */
    public function run();

    /**
     * @return string
     */
    public function getCommand(): string;

    /**
     * @return string
     */
    public function getCommandDescription(): string;
}