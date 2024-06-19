<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Collection;
use Swoole\Process;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ViewCacheCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        $this->call('view:clear');

        $this->paths()->each(function ($path) {
            $this->compileViews($this->bladeFilesIn([$path]));
        });

        $this->info('Blade templates cached successfully!');

        return true;
    }

    /**
     * Get the Blade files in the given path.
     *
     * @param array $paths
     * @return Collection
     */
    protected function bladeFilesIn(array $paths): Collection
    {
        return collect(
            Finder::create()
                ->in($paths)
                ->exclude('vendor')
                ->name('*.blade.php')
                ->files()
        );
    }

    /**
     * Compile the given view files.
     *
     * @param Collection $views
     * @return void
     */
    protected function compileViews(Collection $views): void
    {
        $compiler = app('view')->getEngineResolver()->resolve('blade')->getCompiler();

        $views->map(function (SplFileInfo $file) use ($compiler) {
            $compiler->compile($file->getRealPath());
        });
    }

    /**
     * Get all of the possible view paths.
     */
    protected function paths(): Collection
    {
        $finder = app('view')->getFinder();

        return collect($finder->getPaths())->merge(
            collect($finder->getHints())->flatten(2)
        );
    }

    public function getCommand(): string
    {
        return 'view:cache';
    }

    public function getCommandDescription(): string
    {
        return 'Compile all of the application\'s Blade templates.';
    }
}