<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Container\EntryNotFoundException;
use Mini\Exception\FileNotFoundException;
use Mini\Filesystem\Filesystem;
use Mini\Support\Str;
use Swoole\Process;

abstract class GeneratorStubCommand extends AbstractCommandService
{
    /**
     * Reserved names that cannot be used for generation.
     *
     * @var string[]
     */
    protected array $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
    ];

    protected Filesystem $filesystem;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = app('files');
    }

    /**
     * Get the stub file for the generator.
     * @return string
     */
    abstract protected function getStub(): string;

    /**
     * @param Process|null $process
     * @return void
     * @throws EntryNotFoundException
     * @throws FileNotFoundException
     */
    public function handle(?Process $process): void
    {
        $name = $this->getNameInput();

        if (empty($name)) {
            $this->error("Miss {$this->type} name");
            return;
        }

        if ($this->isReservedName($name)) {
            $this->error('The name "' . $name . '" is reserved by PHP.');
            return;
        }

        $name = $this->qualifyClass($name);

        $path = $this->getPath($name);


        if ($this->alreadyExists($name) && (!$this->hasOption('force') ||
                !$this->option('force'))) {
            $this->error($this->type . ' [' . $name . '] already exists! use --force overwrite the file.');
            return;
        }

        $this->makeDirectory($path);

        $this->filesystem->put($path, $this->sortImports($this->buildClass($name)));

        $this->info($this->type . ' [' . $name . '] created successfully.');
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     * @throws FileNotFoundException|EntryNotFoundException
     */
    protected function buildClass(string $name): string
    {
        $stub = $this->filesystem->get($this->getStub());

        $stud = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);

        return $this->replaceParams($stud);
    }

    /**
     * @param string $stub
     * @return string
     */
    protected function replaceParams(string $stub): string
    {
        return $stub;
    }

    /**
     * Replace the namespace for the given stub.
     * @param string $stub
     * @param string $name
     * @return $this
     */
    protected function replaceNamespace(string &$stub, string $name): self
    {
        $searches = [
            ['DummyNamespace', 'DummyRootNamespace'],
            ['{{ namespace }}', '{{ rootNamespace }}'],
            ['{{namespace}}', '{{rootNamespace}}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [$this->getNamespace($name), $this->rootNamespace()],
                $stub
            );
        }

        return $this;
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return string
     */
    protected function replaceClass(string $stub, string $name): string
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        return str_replace(['DummyClass', '{{ class }}', '{{class}}'], $class, $stub);
    }

    /**
     * Get the full namespace for a given class, without the class name.
     * @param string $name
     * @return string
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Alphabetically sorts the imports for the given stub.
     *
     * @param string $stub
     * @return string
     */
    protected function sortImports(string $stub): string
    {
        if (preg_match('/(?P<imports>(?:use [^;]+;$\n?)+)/m', $stub, $match)) {
            $imports = explode("\n", trim($match['imports']));

            sort($imports);

            return str_replace(trim($match['imports']), implode("\n", $imports), $stub);
        }

        return $stub;
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param string $path
     * @return string
     */
    protected function makeDirectory(string $path): string
    {
        if (!$this->filesystem->isDirectory(dirname($path))) {
            $this->filesystem->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Checks whether the given name is reserved.
     *
     * @param string $name
     * @return bool
     */
    protected function isReservedName(string $name): bool
    {
        $name = strtolower($name);

        return in_array($name, $this->reservedNames, true);
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath(string $name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return base_path('app') . '/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Determine if the class already exists.
     *
     * @param string $rawName
     * @return bool
     */
    protected function alreadyExists(string $rawName): bool
    {
        return $this->filesystem->exists($this->getPath($this->qualifyClass($rawName)));
    }

    /**
     * Get the desired class name from the input.
     * @return string
     */
    protected function getNameInput(): string
    {
        return trim($this->argument('name', $this->getArg(0, '')));
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = stub_path(trim($stub, '/')))
            ? $customPath
            : __DIR__ . '/stubs/' . $stub;
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace(): string
    {
        return app()->getNamespace();
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace;
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass(string $name): string
    {
        $name = ltrim($name, '\\/');

        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $this->getName($name)
        );
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getName(string $name): string
    {
        return $name;
    }
}
