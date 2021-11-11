<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Command;
use Swoole\Process;

class MigrateInstallCommandService extends AbstractCommandService
{
    use Migration;

    /**
     * @param Process $process
     */
    public function handle(Process $process): void
    {
        run(function () {
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
