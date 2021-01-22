<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Command;
use Mini\Support\Coroutine;

class MigrateResetCommandService extends AbstractCommandService
{
    use Migration;

    public function handle()
    {
        Coroutine::create(function () {
            if (!$this->confirmToProceed()) {
                return;
            }
            $this->migrator->setConnection($this->getOpt('database'));
            if (!$this->migrator->repositoryExists()) {
                Command::line('Migration table not found.');
                return;
            }
            $this->migrator->reset(
                [$this->getMigrationPaths()], $this->getOpt('pretend')
            );
        });
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
