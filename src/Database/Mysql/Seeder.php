<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Console\Command;
use Mini\Container\Container;
use Mini\Support\Arr;
use InvalidArgumentException;

abstract class Seeder
{
    /**
     * The container instance.
     *
     * @var \Mini\Container\Container
     */
    protected $container;

    /**
     * The console command instance.
     *
     * @var \Mini\Console\Command
     */
    protected $command;

    /**
     * Seed the given connection from the given path.
     *
     * @param array|string $class
     * @param bool $silent
     * @return $this
     */
    public function call($class, $silent = false)
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            $seeder = $this->resolve($class);

            $name = get_class($seeder);

            if ($silent === false && isset($this->command)) {
                $this->command->getOutput()->writeln("<comment>Seeding:</comment> {$name}");
            }

            $startTime = microtime(true);

            $seeder->__invoke();

            $runTime = round(microtime(true) - $startTime, 2);

            if ($silent === false && isset($this->command)) {
                $this->command->getOutput()->writeln("<info>Seeded:</info>  {$name} ({$runTime} seconds)");
            }
        }

        return $this;
    }

    /**
     * Silently seed the given connection from the given path.
     *
     * @param array|string $class
     * @return void
     */
    public function callSilent($class)
    {
        $this->call($class, true);
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param string $class
     * @return \Mini\Database\Mysql\Seeder
     */
    protected function resolve($class)
    {
        if (isset($this->container)) {
            $instance = $this->container->make($class);

            $instance->setContainer($this->container);
        } else {
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command);
        }

        return $instance;
    }

    /**
     * Set the IoC container instance.
     *
     * @param \Mini\Container\Container $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
     *
     * @param \Mini\Console\Command $command
     * @return $this
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Run the database seeds.
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __invoke()
    {
        if (!method_exists($this, 'run')) {
            throw new InvalidArgumentException('Method [run] missing from ' . get_class($this));
        }

        return isset($this->container)
            ? $this->container->call([$this, 'run'])
            : $this->run();
    }
}
