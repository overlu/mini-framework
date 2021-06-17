<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Exception;
use Mini\Database\Mysql\Migrations\TableGuesser;
use Mini\Support\Command;
use Mini\Support\Str;
use Swoole\Process;

class MakeMigrationCommandService extends AbstractCommandService
{
    use Migration;

    /**
     * @param Process $process
     * @return mixed|void
     * @throws Exception
     */
    public function handle(Process $process)
    {
        run(function () {
            $argFirst = $this->getArgs()[0] ?? null;
            if (!$argFirst) {
                Command::error('no migration file name');
                return;
            }
            $name = Str::snake(trim($this->getArg('name', $argFirst)));
            $table = $this->getOpt('table');
            $create = $this->getOpt('create', false);

            if (!$table && is_string($create)) {
                $table = $create;
                $create = true;
            }

            if (!$table) {
                [$table, $create] = TableGuesser::guess($name);
            }

            $this->writeMigration($name, $table, $create);
        });


//        $this->composer->dumpAutoloads();
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

        Command::info("Created Migration: {$file}");
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
