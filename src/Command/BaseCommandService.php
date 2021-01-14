<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Exception;
use Mini\Console\App;
use Mini\Contracts\Console\CommandInterface;
use Mini\Support\Command;

class BaseCommandService implements CommandInterface
{
    public string $command = 'command';

    public string $description = 'command decriptioon';

    protected App $app;

    public function run()
    {
        Command::info('command:' . $this->app->getCommand());
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getCommandDescription(): string
    {
        return $this->description;
    }

    public function setApp(App $app): self
    {
        $this->app = $app;
        return $this;
    }

    public function __call($name, $arguments)
    {
        return $this->app->$name(...$arguments);
    }
}