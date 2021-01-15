<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Migrations;

use Closure;
use Exception;
use Mini\Exceptions\FileNotFoundException;
use Mini\Filesystem\Filesystem;
use Mini\Support\Str;
use InvalidArgumentException;

class MigrationCreator
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * The custom app stubs directory.
     *
     * @var string
     */
    protected string $customStubPath;

    /**
     * The registered post create hooks.
     *
     * @var array
     */
    protected array $postCreate = [];

    /**
     * Create a new migration creator instance.
     *
     * @param Filesystem $files
     * @param string $customStubPath
     * @return void
     */
    public function __construct(Filesystem $files, string $customStubPath)
    {
        $this->files = $files;
        $this->customStubPath = $customStubPath;
    }

    /**
     * Create a new migration at the given path.
     *
     * @param string $name
     * @param string $path
     * @param string|null $table
     * @param bool $create
     * @return string
     *
     * @throws Exception
     */
    public function create(string $name, string $path, ?string $table = null, bool $create = false): string
    {
        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        $stub = $this->getStub($table, $create);

        $this->files->put(
            $path = $this->getPath($name, $path),
            $this->populateStub($name, $stub, $table)
        );

        // Next, we will fire any hooks that are supposed to fire after a migration is
        // created. Once that is done we'll be ready to return the full path to the
        // migration file so it can be used however it's needed by the developer.
        $this->firePostCreateHooks($table);

        return $path;
    }

    /**
     * Ensure that a migration with the given name doesn't already exist.
     *
     * @param string $name
     * @param string $migrationPath
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function ensureMigrationDoesntAlreadyExist(string $name, ?string $migrationPath = null): void
    {
        if (!empty($migrationPath)) {
            $migrationFiles = $this->files->glob($migrationPath . '/*.php');

            foreach ($migrationFiles as $migrationFile) {
                $this->files->requireOnce($migrationFile);
            }
        }

        if (class_exists($className = $this->getClassName($name))) {
            throw new InvalidArgumentException("A {$className} class already exists.");
        }
    }

    /**
     * Get the migration stub file.
     *
     * @param string|null $table
     * @param bool $create
     * @return string
     * @throws FileNotFoundException
     */
    protected function getStub(?string $table, bool $create): string
    {
        if (is_null($table)) {
            $stub = $this->files->exists($customPath = $this->customStubPath . '/migration.stub')
                ? $customPath
                : $this->stubPath() . '/migration.stub';
        } elseif ($create) {
            $stub = $this->files->exists($customPath = $this->customStubPath . '/migration.create.stub')
                ? $customPath
                : $this->stubPath() . '/migration.create.stub';
        } else {
            $stub = $this->files->exists($customPath = $this->customStubPath . '/migration.update.stub')
                ? $customPath
                : $this->stubPath() . '/migration.update.stub';
        }

        return $this->files->get($stub);
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param string $name
     * @param string $stub
     * @param string|null $table
     * @return string
     */
    protected function populateStub(string $name, string $stub, ?string $table): string
    {
        $stub = str_replace(
            ['DummyClass', '{{ class }}', '{{class}}'],
            $this->getClassName($name), $stub
        );

        // Here we will replace the table place-holders with the table specified by
        // the developer, which is useful for quickly creating a tables creation
        // or update migration from the console instead of typing it manually.
        if (!is_null($table)) {
            $stub = str_replace(
                ['DummyTable', '{{ table }}', '{{table}}'],
                $table, $stub
            );
        }

        return $stub;
    }

    /**
     * Get the class name of a migration name.
     *
     * @param string $name
     * @return string
     */
    protected function getClassName(string $name): string
    {
        return Str::studly($name);
    }

    /**
     * Get the full path to the migration.
     *
     * @param string $name
     * @param string $path
     * @return string
     */
    protected function getPath(string $name, string $path): string
    {
        return $path . '/' . $this->getDatePrefix() . '_' . $name . '.php';
    }

    /**
     * Fire the registered post create hooks.
     *
     * @param string|null $table
     * @return void
     */
    protected function firePostCreateHooks(?string $table): void
    {
        foreach ($this->postCreate as $callback) {
            $callback($table);
        }
    }

    /**
     * Register a post migration create hook.
     *
     * @param Closure $callback
     * @return void
     */
    public function afterCreate(Closure $callback): void
    {
        $this->postCreate[] = $callback;
    }

    /**
     * Get the date prefix for the migration.
     *
     * @return string
     */
    protected function getDatePrefix(): string
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath(): string
    {
        return __DIR__ . '/stubs';
    }

    /**
     * Get the filesystem instance.
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->files;
    }
}
