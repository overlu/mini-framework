<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Command;
use Swoole\Process;

class TestCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        run(function () {
            $this->info(Command::exec(BASE_PATH . '/vendor/phpunit/phpunit/phpunit ./tests'));
            return true;
        });
        return true;
    }

    public function getCommand(): string
    {
        return 'test';
    }

    public function getCommandDescription(): string
    {
        return 'run mini phpunit test.';
    }
}