<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Str;

class MakeControllerCommandService extends GeneratorStubCommand
{
    protected string $type = 'controller';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        $stub = null;

        if (is_null($stub) && $this->option('api')) {
            $stub = 'controller.api.stub';
        }

        if (is_null($stub) && $this->option('websocket')) {
            $stub = 'controller.websocket.stub';
        }


        $stub = $stub ?? 'controller.plain.stub';

        return $this->resolveStubPath($stub);
    }

    /**
     * Get the default namespace for the class.
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $this->option('websocket') ? $rootNamespace . '\Controllers\Websocket' : $rootNamespace . '\Controllers\Http';
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'Controller') ? $name : $name . 'Controller';
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:controller';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new controller file.
                   <blue>{--api : Generator api controller file.}
                   {--websocket : Generator websocket controller file.}</blue>';
    }
}
