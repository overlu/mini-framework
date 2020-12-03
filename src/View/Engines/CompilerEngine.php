<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Engines;

use ErrorException;
use Mini\Filesystem\Filesystem;
use Mini\View\Compilers\CompilerInterface;
use Throwable;

class CompilerEngine extends PhpEngine
{
    /**
     * The Blade compiler instance.
     *
     * @var CompilerInterface
     */
    protected CompilerInterface $compiler;

    /**
     * A stack of the last compiled templates.
     *
     * @var array
     */
    protected array $lastCompiled = [];

    /**
     * Create a new compiler engine instance.
     *
     * @param CompilerInterface $compiler
     * @param Filesystem|null $files
     * @return void
     */
    public function __construct(CompilerInterface $compiler, ?Filesystem $files = null)
    {
        parent::__construct($files ?: new Filesystem);

        $this->compiler = $compiler;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param string $path
     * @param array $data
     * @return string
     */
    public function get(string $path, array $data = []): string
    {
        $this->lastCompiled[] = $path;

        // If this given view has expired, which means it has simply been edited since
        // it was last compiled, we will re-compile the views so we can evaluate a
        // fresh copy of the view. We'll pass the compiler the path of the view.
        if ($this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        // Once we have the path to the compiled file, we will evaluate the paths with
        // typical PHP just like any other templates. We also keep a stack of views
        // which have been rendered for right exception messages to be generated.
        $results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);

        array_pop($this->lastCompiled);

        return $results;
    }

    /**
     * Handle a view exception.
     *
     * @param \Throwable $e
     * @param int $obLevel
     * @return void
     *
     * @throws \Throwable
     */
    protected function handleViewException(Throwable $e, int $obLevel): void
    {
        $e = new ErrorException($this->getMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e);

        parent::handleViewException($e, $obLevel);
    }

    /**
     * Get the exception message for an exception.
     *
     * @param \Throwable $e
     * @return string
     */
    protected function getMessage(Throwable $e): string
    {
        return $e->getMessage() . ' (View: ' . realpath(last($this->lastCompiled)) . ')';
    }

    /**
     * Get the compiler implementation.
     *
     * @return CompilerInterface
     */
    public function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }
}
