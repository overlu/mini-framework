<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Command\BaseCommandService;
use Mini\Crontab\Crontab;

class RunCrontabCommandService extends BaseCommandService
{
    public string $command = 'crontab:run';

    public string $description = 'run crontab task';

    public function run()
    {
        Crontab::run();
    }
}