<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Container\EntryNotFoundException;
use Mini\Exception\FileNotFoundException;
use Mini\Support\Str;
use Swoole\Process;

class MakeMailCommandService extends GeneratorStubCommand
{
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'mail';

    /**
     * Execute the console command.
     *
     * @param Process|null $process
     * @return bool
     * @throws EntryNotFoundException
     * @throws FileNotFoundException
     */
    public function handle(?Process $process): bool
    {
        if (parent::handle($process) === false && !$this->option('force')) {
            return false;
        }

        if ($this->option('markdown') !== false) {
            $this->writeMarkdownTemplate();
        }

        return true;
    }

    /**
     * Write the Markdown template for the mailable.
     *
     * @return void
     */
    protected function writeMarkdownTemplate(): void
    {
        $path = $this->viewPath(
            str_replace('.', '/', $this->getView()) . '.blade.php'
        );

        if (!$this->filesystem->isDirectory(dirname($path))) {
            $this->filesystem->makeDirectory(dirname($path), 0755, true);
        }

        $this->filesystem->put($path, file_get_contents(__DIR__ . '/stubs/markdown.stub'));
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     * @throws EntryNotFoundException
     * @throws FileNotFoundException
     */
    protected function buildClass(string $name): string
    {
        $class = str_replace(
            '{{ subject }}',
            Str::headline(str_replace($this->getNamespace($name) . '\\', '', $name)),
            parent::buildClass($name)
        );

        if ($this->option('markdown') !== false) {
            $class = str_replace(['DummyView', '{{ view }}'], $this->getView(), $class);
        }

        return $class;
    }

    /**
     * Get the view name.
     *
     * @return string
     */
    protected function getView(): string
    {
        $view = $this->option('markdown');

        if (!is_string($view) || !$view) {
            $name = str_replace('\\', '/', $this->getNameInput());

            $view = collect(explode('/', $name))
                ->map(fn($part) => Str::kebab($part))
                ->implode('.');
        }

        return 'mail.' . $view;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath(
            $this->option('markdown') !== false
                ? 'markdown-mail.stub'
                : 'mail.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . '\Mail';
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:mail';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'Create a new email class.
                   <blue>{--markdown : Create a new Markdown template for the mailable.}</blue>';
    }
}
