<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Console\Command;
use Mini\Container\Container;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\Arr;
use InvalidArgumentException;

abstract class Seeder
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * The console command instance.
     *
     * @var Command
     */
    protected Command $command;

    /**
     * Seed the given connection from the given path.
     *
     * @param array|string $class
     * @param bool $silent
     * @return $this
     */
    public function call($class, bool $silent = false): self
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
    public function callSilent($class): void
    {
        $this->call($class, true);
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param string $class
     * @return Seeder
     * @throws BindingResolutionException
     */
    protected function resolve(string $class): Seeder
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
     * @param Container $container
     * @return $this
     */
    public function setContainer(Container $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
     *
     * @param Command $command
     * @return $this
     */
    public function setCommand(Command $command): self
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Run the database seeds.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
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
