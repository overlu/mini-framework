<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Str;

class MakeEventCommandService extends GeneratorStubCommand
{
    protected string $type = 'event';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('event.stub');
    }

    /**
     * Get the default namespace for the class.
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . '\Events';
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'Event') ? $name : $name . 'Event';
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:event';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new event file.';
    }
}
