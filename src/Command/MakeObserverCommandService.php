<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Str;

class MakeObserverCommandService extends GeneratorStubCommand
{
    protected string $type = 'observer';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath(is_null($this->getOpt('model')) ? 'observer.plain.stub' : 'observer.model.stub');
    }

    protected function replaceParams(string $stub): string
    {
        if (is_null($model = $this->getOpt('model'))) {
            return $stub;
        }
        $model = ucfirst(str_replace('App\\Models\\', '', trim($model, '\\')));
        $modelVar = lcfirst($model);
        return str_replace([
            '{{ model }}', '{{ modelVar }}'
        ], [
            $model, $modelVar
        ], $stub);
    }

    /**
     * Get the default namespace for the class.
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . '\Observers';
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'Observer') ? $name : $name . 'Observer';
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:observer';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new observer file.
                   <blue>{--model= : Generator a new observer file with model.}</blue>';
    }
}
