<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Str;

class MakeCommandCommandService extends GeneratorStubCommand
{
    protected string $type = 'command';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('command.stub');
    }

    /**
     * Get the default namespace for the class.
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . '\Console';
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'CommandService') ? $name : $name . 'CommandService';
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:command';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new command file.';
    }
}
