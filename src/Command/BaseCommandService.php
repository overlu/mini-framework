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
    protected App $app;

    /**
     * @param null $class
     * @throws Exception
     */
    public static function check($class): void
    {
        if (!isset($class::$command)) {
            throw new \RuntimeException('no static::$command in ' . $class);
        }
    }

    public function run()
    {
        Command::info('command:' . $this->app->getCommand());
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