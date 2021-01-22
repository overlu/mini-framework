<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Exception;
use Mini\Support\Command;
use Mini\Support\Coroutine;

class TestCommandService extends AbstractCommandService
{
    /**
     * @return mixed|void
     * @throws Exception
     */
    public function handle()
    {
        Coroutine::create(function () {
            Command::info(Command::exec(BASE_PATH . '/vendor/phpunit/phpunit/phpunit ./tests'));
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