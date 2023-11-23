<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Str;

class MakeListenerCommandService extends GeneratorStubCommand
{
    protected string $type = 'listener';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('listener.stub');
    }

    /**
     * Get the default namespace for the class.
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . '\Listeners';
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'Listener') ? $name : $name . 'Listener';
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:listener';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new listener file.';
    }
}
