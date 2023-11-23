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
     * @param Process $process
     * @return void
     */
    public function handle(Process $process): void
    {
        run(static function () {
            $this->info(Command::exec(BASE_PATH . '/vendor/phpunit/phpunit/phpunit ./tests'));
        });
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