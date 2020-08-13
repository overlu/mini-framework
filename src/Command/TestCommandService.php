<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Exception;
use Mini\Support\Command;

class TestCommandService extends BaseCommandService
{
    public static string $command = 'test';

    public static string $description = 'run mini phpunit test.';

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function run()
    {
        Command::info(Command::exec(BASE_PATH . '/vendor/phpunit/phpunit/phpunit ./tests'));
    }
}