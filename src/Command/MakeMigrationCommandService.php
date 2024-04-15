<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Exception;
use Mini\Database\Mysql\Migrations\TableGuesser;
use Mini\Support\Str;
use Swoole\Process;

class MakeMigrationCommandService extends AbstractCommandService
{
    use Migration;

    protected string $type = 'migration';

    /**
     * @param Process $process
     * @return void
     * @throws Exception
     */
    public function handle(?Process $process): void
    {
        run(function () {
            $name = trim($this->argument('name', $this->getArg(0, '')));
            if (empty($name)) {
                $this->error("Miss {$this->type} name");
                return;
            }
            $table = $this->getOpt('table');
            $create = $this->getOpt('create', false);

            if (!$table && is_string($create)) {
                $table = $create;
                $create = true;
            }

            $name = Str::snake($name);

            if (!$table) {
                [$table, $create] = TableGuesser::guess($name);
            }

            $this->writeMigration($name, $table, $create);
        });
    }

    /**
     * @param $name
     * @param $table
     * @param $create
     * @throws Exception
     */
    protected function writeMigration($name, $table, $create): void
    {
        $file = $this->creator->create(
            $name, $this->getMigrationPaths(), $table, $create
        );

        if (!$this->getOpt('fullpath')) {
            $file = pathinfo($file, PATHINFO_FILENAME);
        }

        $this->info("Created Migration: {$file}");
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'make:migration';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'create a new migration file.';
    }
}
