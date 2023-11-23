<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Str;

class MakeProviderCommandService extends GeneratorStubCommand
{
    protected string $type = 'provider';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('provider.stub');
    }

    /**
     * Get the default namespace for the class.
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . '\Providers';
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'ServiceProvider') ? $name : $name . 'ServiceProvider';
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:provider';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new provider file.';
    }
}
