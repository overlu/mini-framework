<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

class MigrateRollbackCommandService extends BaseCommandService
{
    use Migration;

    public string $command = 'migrate:rollback';

    public string $description = 'rollback the last database migration.';

    public function run()
    {
        go(function () {
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
}
