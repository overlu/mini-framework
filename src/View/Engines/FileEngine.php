<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Engines;

use Mini\Contracts\View\Engine;
use Mini\Support\Filesystem;

class FileEngine implements Engine
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * Create a new file engine instance.
     *
     * @param Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param string $path
     * @param array $data
     * @return string
     * @throws \Mini\Exceptions\FileNotFoundException
     */
    public function get(string $path, array $data = []): string
    {
        return $this->files->get($path);
    }
}
