<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Database\Mysql\Migrations\TableGuesser;
use Mini\Support\Command;
use Mini\Support\Str;

class MakeMigrationCommandService extends BaseCommandService
{
    use Migration;

    public static string $command = 'make:migrate';

    public static string $description = 'create a new migration file.';

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function run()
    {
        $argFirst = $this->getArgs()[0] ?? null;
        if (!$argFirst) {
            Command::error('no migration file name');
            return;
        }
        $name = Str::snake(trim($this->getArg('name', $argFirst)));
        $table = $this->app->getOpt('table');
        $create = $this->app->getOpt('create', false);

        if (!$table && is_string($create)) {
            $table = $create;
            $create = true;
        }

        if (!$table) {
            [$table, $create] = TableGuesser::guess($name);
        }

        $this->writeMigration($name, $table, $create);

//        $this->composer->dumpAutoloads();
    }

    /**
     * @param $name
     * @param $table
     * @param $create
     * @throws \Exception
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
}
