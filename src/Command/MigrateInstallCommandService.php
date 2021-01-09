<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Command;

class MigrateInstallCommandService extends BaseCommandService
{
    use Migration;

    public static string $command = 'migrate:install';

    public static string $description = 'create the migration repository.';

    public function run()
    {
        go(function () {
            $this->repository->setSource($this->getOpt('database'));

            $this->repository->createRepository();

            Command::info('Migration table created successfully.');
        });
    }
}
