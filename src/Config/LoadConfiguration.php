<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Config;

use Exception;
use Mini\Contracts\Config\Repository as RepositoryContract;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

trait LoadConfiguration
{
    /**
     * Bootstrap the given application.
     *
     * @return void
     * @throws Exception
     */
    public function bootstrap(): void
    {
        $this->loadConfigurationFiles();
    }

    protected function loadConfigurationFiles(): void
    {
        $files = $this->getConfigurationFiles();

        if (!isset($files['app'], $files['servers'])) {
            throw new RuntimeException('Unable to load the "app, servers" configuration file.');
        }

        foreach ($files as $key => $path) {
            $this->repository[$key] = require $path;
        }
    }

    /**
     * Get all of the configuration files for the application.
     *
     * @return array
     */
    protected function getConfigurationFiles(): array
    {
        $files = [];

        foreach (Finder::create()->files()->name('*.php')->in(CONFIG_PATH) as $file) {
            $directory = $this->getNestedDirectory($file);

            $files[$directory . basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * Get the configuration file nesting path.
     *
     * @param SplFileInfo $file
     * @return string
     */
    protected function getNestedDirectory(SplFileInfo $file): string
    {
        $directory = rtrim($file->getPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($nested = trim(str_replace(CONFIG_PATH, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested) . '.';
        }

        return $nested;
    }
}
