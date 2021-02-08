<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Console;

use Swoole\Process;

interface CommandInterface
{
    /**
     * @param Process $process
     * @return mixed
     */
    public function handle(Process $process);

    /**
     * @return string
     */
    public function getCommand(): string;

    /**
     * @return string
     */
    public function getCommandDescription(): string;
}