<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Coroutine;
use Swoole\Process;

class MigrateRollbackCommandService extends AbstractCommandService
{
    use Migration;

    public function handle(Process $process)
    {
        Coroutine::create(function () {
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
