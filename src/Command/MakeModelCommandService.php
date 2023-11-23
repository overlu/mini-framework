<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

class MakeModelCommandService extends GeneratorStubCommand
{
    protected string $type = 'model';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath(is_null($this->getOpt('table')) ? 'model.plain.stub' : 'model.table.stub');
    }

    protected function replaceParams(string $stub): string
    {
        if (is_null($table = $this->getOpt('table'))) {
            return $stub;
        }
        return str_replace('{{ table }}', $table, $stub);
    }

    /**
     * Get the default namespace for the class.
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . '\Models';
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        return ucfirst($name);
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:model';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new model file.
                   <blue>{--table= : Generator a new model file with table.}</blue>';
    }
}
