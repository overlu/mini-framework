<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Facades\File;
use RuntimeException;
use Swoole\Process;

class ViewClearCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return void
     */
    public function handle(?Process $process): void
    {
        $path = config('view.compiled');

        if (!$path) {
            throw new RuntimeException('View path not found.');
        }

        File::delete(File::glob("{$path}/*"));

        $this->info('Compiled views cleared!');
    }

    public function getCommand(): string
    {
        return 'view:clear';
    }

    public function getCommandDescription(): string
    {
        return 'Clear all compiled view files.';
    }
}