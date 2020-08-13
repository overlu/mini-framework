<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

class MigrateRefreshCommandService extends BaseCommandService
{
    use Migration;

    public static string $command = 'migrate:refresh';

    public static string $description = 'reset and re-run all migrations.';

    public function run()
    {
        if (!$this->confirmToProceed()) {
            return;
        }
        $database = $this->getOpt('database');
        $path = $this->getOpt('path');
        $step = $this->getOpt('step', 0);
        if ($step > 0) {
            $this->runRollback($database, $path, $step);
        } else {
            $this->runReset($database, $path);
        }
        $this->call('migrate', array_filter([
            '--database' => $database,
            '--path' => $path,
            '--realpath' => $this->getOpt('realpath'),
            '--force' => true,
        ]));
    }

    protected function runRollback($database, $path, $step): void
    {
        $this->call('migrate:rollback', array_filter([
            '--database' => $database,
            '--path' => $path,
            '--realpath' => $this->getOpt('realpath'),
            '--step' => $step,
            '--force' => true,
        ]));
    }

    protected function runReset($database, $path): void
    {
        $this->call('migrate:reset', array_filter([
            '--database' => $database,
            '--path' => $path,
            '--realpath' => $this->getOpt('realpath'),
            '--force' => true,
        ]));
    }
}
