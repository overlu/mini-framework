<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Filesystem;

use Mini\Contracts\File;

interface Cloud extends File
{
    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     * @return string
     */
    public function url(string $path): string;
}
