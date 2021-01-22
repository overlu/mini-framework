<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Command\AbstractCommandService;
use Mini\Crontab\Crontab;

class RunCrontabCommandService extends AbstractCommandService
{
    public function handle()
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