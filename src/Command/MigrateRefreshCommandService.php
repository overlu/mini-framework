<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Swoole\Process;

class MigrateRefreshCommandService extends AbstractCommandService
{
    use Migration;

    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        if (!$this->confirmToProceed()) {
            return false;
        }
        $database = $this->getOpt('database');
        $path = $this->getOpt('path');
        $step = $this->getOpt('step', 0);
        if ($step > 0) {
            $this->runRollback($database, $path, $step);
        } else {
            $this->runReset($database, $path);
        }
        $this->call('migrate', [], array_filter([
            'database' => $database,
            'path' => $path,
            'realpath' => $this->getOpt('realpath'),
            'force' => true,
        ]));

        return true;
    }

    protected function runRollback($database, $path, $step): void
    {
        $this->call('migrate:rollback', [], array_filter([
            'database' => $database,
            'path' => $path,
            'realpath' => $this->getOpt('realpath'),
            'step' => $step,
            'force' => true,
        ]));
    }

    protected function runReset($database, $path): void
    {
        $this->call('migrate:reset', [], array_filter([
            'database' => $database,
            'path' => $path,
            'realpath' => $this->getOpt('realpath'),
            'force' => true,
        ]));
    }

    public function getCommand(): string
    {
        return 'migrate:refresh';
    }

    public function getCommandDescription(): string
    {
        return 'reset and re-run all migrations.';
    }
}
