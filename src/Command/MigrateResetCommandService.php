<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Swoole\Process;

class MigrateResetCommandService extends AbstractCommandService
{
    use Migration;

    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        run(function () {
            if (!$this->confirmToProceed()) {
                return false;
            }
            $this->migrator->setConnection($this->getOpt('database'));
            if (!$this->migrator->repositoryExists()) {
                $this->message('Migration table not found.');
                return false;
            }
            $this->migrator->reset(
                [$this->getMigrationPaths()], $this->getOpt('pretend')
            );
            return true;
        });

        return true;
    }

    public function getCommand(): string
    {
        return 'migrate:reset';
    }

    public function getCommandDescription(): string
    {
        return 'rollback all database migrations.';
    }
}
