<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Coroutine;
use Swoole\Process;

class MigrateCommandService extends AbstractCommandService
{
    use Migration;

    public function handle(Process $process)
    {
        Coroutine::create(function () {
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

    public function getCommand(): string
    {
        return 'migrate';
    }

    public function getCommandDescription(): string
    {
        return 'migrate the database.';
    }
}
