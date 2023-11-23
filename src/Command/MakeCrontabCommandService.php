<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Str;

class MakeCrontabCommandService extends GeneratorStubCommand
{
    protected string $type = 'crontab';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('crontab.stub');
    }

    /**
     * Get the default namespace for the class.
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . '\Crontab';
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'CrontabTask') ? $name : $name . 'CrontabTask';
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:crontab';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new crontab file.';
    }
}
