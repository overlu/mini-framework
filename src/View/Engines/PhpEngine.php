<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Engines;

use Mini\Contracts\View\Engine;
use Mini\Filesystem\Filesystem;
use Throwable;

class PhpEngine implements Engine
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem|null
     */
    protected ?Filesystem $files;

    /**
     * Create a new file engine instance.
     *
     * @param Filesystem|null $files
     */
    public function __construct(?Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param string $path
     * @param array $data
     * @return string
     * @throws Throwable
     */
    public function get(string $path, array $data = []): string
    {
        return $this->evaluatePath($path, $data);
    }

    /**
     * Get the evaluated contents of the view at the given path.
     *
     * @param string $path
     * @param array $data
     * @return string
     * @throws Throwable
     */
    protected function evaluatePath(string $path, array $data): string
    {
        $obLevel = ob_get_level();

        ob_start();

        // We'll evaluate the contents of the view inside a try/catch block so we can
        // flush out any stray output that might get out before an error occurs or
        // an exception is thrown. This prevents any partial views from leaking.
        try {
            $this->files->getRequire($path, $data);
        } catch (Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
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
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}
