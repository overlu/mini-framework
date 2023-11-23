<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Str;

class MakeMiddlewareCommandService extends GeneratorStubCommand
{
    protected string $type = 'middleware';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('middleware.stub');
    }

    /**
     * Get the default namespace for the class.
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . '\Middlewares';
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'Middleware') ? $name : $name . 'Middleware';
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:middleware';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new middleware file.';
    }
}
