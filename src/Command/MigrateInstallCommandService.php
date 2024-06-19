<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Swoole\Process;

class MigrateInstallCommandService extends AbstractCommandService
{
    use Migration;

    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        run(function () {
            $this->repository->setSource($this->getOpt('database'));

            $this->repository->createRepository();

            $this->info('Migration table created successfully.');
            return true;
        });
        return true;
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
