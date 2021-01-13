<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Command;

class MigrateResetCommandService extends BaseCommandService
{
    use Migration;

    public string $command = 'migrate:reset';

    public string $description = 'rollback all database migrations.';

    public function run()
    {
        go(function (){
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
}
