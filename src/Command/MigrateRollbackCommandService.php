<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Swoole\Process;

class MigrateRollbackCommandService extends AbstractCommandService
{
    use Migration;

    public function handle(Process $process): void
    {
        run(function () {
            if (!$this->confirmToProceed()) {
                return;
            }
            $this->migrator->setConnection($this->getOpt('database'));
            $this->migrator->rollback(
                $this->getMigrationPaths(), [
                    'pretend' => $this->getOpt('pretend'),
                    'step' => (int)$this->getOpt('step'),
                ]
            );
        });
    }

    public function getCommand(): string
    {
        return 'migrate:rollback';
    }

    public function getCommandDescription(): string
    {
        return 'rollback the last database migration.';
    }
}
