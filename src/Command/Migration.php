<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Database\Mysql\Migrations\DatabaseMigrationRepository;
use Mini\Database\Mysql\Migrations\MigrationCreator;
use Mini\Database\Mysql\Migrations\MigrationRepositoryInterface;
use Mini\Database\Mysql\Migrations\Migrator;
use Mini\Filesystem\Filesystem;

trait Migration
{
    protected Migrator $migrator;
    protected MigrationRepositoryInterface $repository;
    protected Filesystem $filesystem;
    protected MigrationCreator $creator;

    public function __construct()
    {
        $dbManger = app('db');
        $this->filesystem = app('files');
        $this->repository = new DatabaseMigrationRepository($dbManger, 'migrations');
        $this->migrator = new Migrator($this->repository, $dbManger, $this->filesystem);
        $this->creator = new MigrationCreator($this->filesystem, stub_path());
    }

    public function confirmToProceed($warning = 'Application In Production! If need continue, use --force.', $callback = null): bool
    {
        $callback = is_null($callback) ? $this->getDefaultConfirmCallback() : $callback;

        $shouldConfirm = value($callback);

        if ($shouldConfirm) {
            if ($this->getBoolOpt('force')) {
                return true;
            }
            $this->warning($warning);
            return false;
        }
        return true;
    }

    protected function getDefaultConfirmCallback(): callable
    {
        return static function () {
            return env('APP_ENV') === 'production';
        };
    }

    protected function getMigrationPaths(): string
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
    }
}
