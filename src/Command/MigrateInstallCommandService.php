<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Command;
use Mini\Support\Coroutine;

class MigrateInstallCommandService extends AbstractCommandService
{
    use Migration;

    public function handle()
    {
        Coroutine::create(function () {
            $this->repository->setSource($this->getOpt('database'));

            $this->repository->createRepository();

            Command::info('Migration table created successfully.');
        });
    }

    public function getCommand(): string
    {
        return 'migrate:install';
    }

    public function getCommandDescription(): string
    {
        return 'create the migration repository.';
    }
}
