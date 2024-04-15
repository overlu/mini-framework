<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Crontab\Crontab;
use Swoole\Process;

class RunCrontabCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return void
     */
    public function handle(?Process $process): void
    {
        Crontab::run();
    }

    public function getCommand(): string
    {
        return 'crontab:run';
    }

    public function getCommandDescription(): string
    {
        return 'run mini crontab task';
    }
}