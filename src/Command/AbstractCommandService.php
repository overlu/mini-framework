<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Exception;
use Mini\Console\App;
use Mini\Console\Artisan;
use Mini\Contracts\Console\CommandInterface;
use Mini\Support\Command;
use Swoole\Process;

/**
 * Class AbstractCommandService
 * @package Mini\Command
 * @mixin App
 */
abstract class AbstractCommandService implements CommandInterface
{
    protected App $app;

    /**
     * run console
     * @param Process $process
     * @return mixed
     */
    abstract public function handle(Process $process);

    /**
     * get command
     * @return string
     */
    abstract public function getCommand(): string;

    /**
     * get command description
     * @return string
     */
    abstract public function getCommandDescription(): string;

    /**
     * @param App $app
     * @return $this
     */
    public function setApp(App $app): self
    {
        $this->app = $app;
        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->app->$name(...$arguments);
    }
}