<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

class MigrateCommandService extends BaseCommandService
{
    use Migration;

    public string $command = 'migrate';

    public string $description = 'migrate the database.';

    public function run()
    {
        go(function (){
            if (!$this->confirmToProceed()) {
                return;
            }
            $this->prepareDatabase();
            $this->migrator->run([$this->getMigrationPaths()], [
                'pretend' => $this->getOpt('pretend'),
                'step' => $this->getOpt('step'),
            ]);
        });
    }

    protected function prepareDatabase(): void
    {
        $this->migrator->setConnection($this->getOpt('database'));
        if (!$this->migrator->repositoryExists()) {
            $this->app->call('migrate:install', array_filter([
                'database' => $this->getOpt('database'),
            ]));
        }
    }
}
