<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\App;
use Mini\Contracts\Console\CommandInterface;
use Swoole\Process;

/**
 * Class AbstractCommandService
 * @package Mini\Command
 * @mixin App
 */
abstract class AbstractCommandService implements CommandInterface
{
    protected App $app;

    public function __construct()
    {

    }

    /**
     * run console
     * @param Process $process
     * @return void
     */
    abstract public function handle(Process $process): void;

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