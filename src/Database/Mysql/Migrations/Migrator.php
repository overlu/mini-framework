<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Migrations;

use Mini\Contracts\Events\Dispatcher;
use Mini\Database\Mysql\Connection;
use Mini\Database\Mysql\ConnectionInterface;
use Mini\Database\Mysql\ConnectionResolverInterface as Resolver;
use Mini\Database\Mysql\Events\MigrationEnded;
use Mini\Database\Mysql\Events\MigrationsEnded;
use Mini\Database\Mysql\Events\MigrationsStarted;
use Mini\Database\Mysql\Events\MigrationStarted;
use Mini\Database\Mysql\Events\NoPendingMigrations;
use Mini\Database\Mysql\Schema\Grammars\Grammar;
use Mini\Support\Command;
use Mini\Filesystem\Filesystem;
use Mini\Support\Arr;
use Mini\Support\Collection;
use Mini\Support\Str;
use Mini\Database\Mysql\OutputInterface;
use Throwable;

/**
 * Class Migrator
 * @package Mini\Database\Mysql\Migrations
 */
class Migrator
{
    /**
     * The event dispatcher instance.
     *
     * @var Dispatcher
     */
    protected ?Dispatcher $events;

    /**
     * The migration repository implementation.
     *
     * @var MigrationRepositoryInterface
     */
    protected MigrationRepositoryInterface $repository;

    /**
     * The filesystem instance.
     *
<<<<<<< HEAD
     * @var \Mini\Filesystem\Filesystem
=======
     * @var Filesystem
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected Filesystem $files;

    /**
     * The connection resolver instance.
     *
     * @var Resolver
     */
    protected Resolver $resolver;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected string $connection;

    /**
     * The paths to all of the migration files.
     *
     * @var array
     */
    protected array $paths = [];

    /**
     * The output interface implementation.
     *
     * @var OutputInterface
     */
    protected OutputInterface $output;

    /**
     * Create a new migrator instance.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Migrations\MigrationRepositoryInterface $repository
     * @param \Mini\Database\Mysql\ConnectionResolverInterface $resolver
     * @param \Mini\Filesystem\Filesystem $files
     * @param \Mini\Contracts\Events\Dispatcher|null $dispatcher
=======
     * @param MigrationRepositoryInterface $repository
     * @param Resolver $resolver
     * @param Filesystem $files
     * @param Dispatcher|null $dispatcher
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return void
     */
    public function __construct(MigrationRepositoryInterface $repository,
                                Resolver $resolver,
                                Filesystem $files,
                                ?Dispatcher $dispatcher = null)
    {
        $this->files = $files;
        $this->events = $dispatcher;
        $this->resolver = $resolver;
        $this->repository = $repository;
    }

    /**
     * Run the pending migrations at a given path.
     *
     * @param array|string $paths
     * @param array $options
     * @return array
     * @throws Throwable
     */
    public function run($paths = [], array $options = []): array
    {
        // Once we grab all of the migration files for the path, we will compare them
        // against the migrations that have already been run for this package then
        // run each of the outstanding migrations against a database connection.
        $files = $this->getMigrationFiles($paths);

        $this->requireFiles($migrations = $this->pendingMigrations(
            $files, $this->repository->getRan()
        ));

        // Once we have all these migrations that are outstanding we are ready to run
        // we will go ahead and run them "up". This will execute each migration as
        // an operation against a database. Then we'll return this list of them.
        $this->runPending($migrations, $options);

        return $migrations;
    }

    /**
     * Get the migration files that have not yet run.
     *
     * @param array $files
     * @param array $ran
     * @return array
     */
    protected function pendingMigrations(array $files, array $ran): array
    {
        return Collection::make($files)
            ->reject(function ($file) use ($ran) {
                return in_array($this->getMigrationName($file), $ran, true);
            })->values()->all();
    }

    /**
     * Run an array of migrations.
     *
     * @param array $migrations
     * @param array $options
     * @return void
     * @throws Throwable
     */
    public function runPending(array $migrations, array $options = []): void
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the migrations have been run against this database system.
        if (count($migrations) === 0) {
            $this->fireMigrationEvent(new NoPendingMigrations('up'));

            Command::info('Nothing to migrate.');

            return;
        }

        // Next, we will get the next batch number for the migrations so we can insert
        // correct batch number in the database migrations repository when we store
        // each migration's execution. We will also extract a few of the options.
        $batch = $this->repository->getNextBatchNumber();

        $pretend = $options['pretend'] ?? false;

        $step = $options['step'] ?? false;

        $this->fireMigrationEvent(new MigrationsStarted);

        // Once we have the array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        foreach ($migrations as $file) {
            $this->runUp($file, $batch, $pretend);

            if ($step) {
                $batch++;
            }
        }

        $this->fireMigrationEvent(new MigrationsEnded);
    }

    /**
     * Run "up" a migration instance.
     *
     * @param string $file
     * @param int $batch
     * @param bool $pretend
     * @return void
     * @throws Throwable
     */
    protected function runUp($file, $batch, $pretend): void
    {
        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        if ($pretend) {
            return $this->pretendToRun($migration, 'up');
        }

        Command::line("Migrating: {$name}");

        $startTime = microtime(true);

        $this->runMigration($migration, 'up');

        $runTime = round(microtime(true) - $startTime, 2);

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($name, $batch);

        Command::info("Migrated:  {$name} ({$runTime} seconds)");
    }

    /**
     * Rollback the last migration operation.
     *
     * @param array|string $paths
     * @param array $options
     * @return array
     * @throws Throwable
     */
    public function rollback($paths = [], array $options = []): array
    {
        // We want to pull in the last batch of migrations that ran on the previous
        // migration operation. We'll then reverse those migrations and run each
        // of them "down" to reverse the last migration "operation" which ran.
        $migrations = $this->getMigrationsForRollback($options);

        if (count($migrations) === 0) {
            $this->fireMigrationEvent(new NoPendingMigrations('down'));

            Command::info('Nothing to rollback.');

            return [];
        }

        return $this->rollbackMigrations($migrations, $paths, $options);
    }

    /**
     * Get the migrations for a rollback operation.
     *
     * @param array $options
     * @return array
     */
    protected function getMigrationsForRollback(array $options): array
    {
        if (($steps = $options['step'] ?? 0) > 0) {
            return $this->repository->getMigrations($steps);
        }

        return $this->repository->getLast();
    }

    /**
     * Rollback the given migrations.
     *
     * @param array $migrations
     * @param array|string $paths
     * @param array $options
     * @return array
     * @throws Throwable
     */
    protected function rollbackMigrations(array $migrations, $paths, array $options): array
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getMigrationFiles($paths));

        $this->fireMigrationEvent(new MigrationsStarted);

        // Next we will run through all of the migrations and call the "down" method
        // which will reverse each migration in order. This getLast method on the
        // repository already returns these migration's names in reverse order.
        foreach ($migrations as $migration) {
            $migration = (object)$migration;

            if (!$file = Arr::get($files, $migration->migration)) {
                Command::error("Migration not found: {$migration->migration}");

                continue;
            }

            $rolledBack[] = $file;

            $this->runDown(
                $file, $migration,
                $options['pretend'] ?? false
            );
        }

        $this->fireMigrationEvent(new MigrationsEnded);

        return $rolledBack;
    }

    /**
     * Rolls all of the currently applied migrations back.
     *
     * @param array|string $paths
     * @param bool $pretend
     * @return array
     */
    public function reset($paths = [], bool $pretend = false): array
    {
        // Next, we will reverse the migration list so we can run them back in the
        // correct order for resetting this database. This will allow us to get
        // the database back into its "empty" state ready for the migrations.
        $migrations = array_reverse($this->repository->getRan());

        if (count($migrations) === 0) {
            Command::info('Nothing to rollback.');

            return [];
        }

        return $this->resetMigrations($migrations, $paths, $pretend);
    }

    /**
     * Reset the given migrations.
     *
     * @param array $migrations
     * @param array $paths
     * @param bool $pretend
     * @return array
     * @throws Throwable
     */
    protected function resetMigrations(array $migrations, array $paths, bool $pretend = false): array
    {
        // Since the getRan method that retrieves the migration name just gives us the
        // migration name, we will format the names into objects with the name as a
        // property on the objects so that we can pass it to the rollback method.
        $migrations = collect($migrations)->map(function ($m) {
            return (object)['migration' => $m];
        })->all();

        return $this->rollbackMigrations(
            $migrations, $paths, compact('pretend')
        );
    }

    /**
     * Run "down" a migration instance.
     *
     * @param string $file
     * @param object $migration
     * @param bool $pretend
     * @return void
     * @throws Throwable
     */
    protected function runDown(string $file, object $migration, bool $pretend): void
    {
        // First we will get the file name of the migration so we can resolve out an
        // instance of the migration. Once we get an instance we can either run a
        // pretend execution of the migration or we can run the real migration.
        $instance = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        Command::line("Rolling back: {$name}");

        if ($pretend) {
            return $this->pretendToRun($instance, 'down');
        }

        $startTime = microtime(true);

        $this->runMigration($instance, 'down');

        $runTime = round(microtime(true) - $startTime, 2);

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($migration);

        Command::info("Rolled back:  {$name} ({$runTime} seconds)");
    }

    /**
     * Run a migration inside a transaction if the database supports it.
     *
     * @param object $migration
     * @param string $method
     * @return void
     * @throws Throwable
     */
    protected function runMigration(object $migration, string $method): void
    {
        $connection = $this->resolveConnection(
            $migration->getConnection()
        );

        $callback = function () use ($migration, $method) {
            if (method_exists($migration, $method)) {
                $this->fireMigrationEvent(new MigrationStarted($migration, $method));

                $migration->{$method}();

                $this->fireMigrationEvent(new MigrationEnded($migration, $method));
            }
        };

        $this->getSchemaGrammar($connection)->supportsSchemaTransactions()
        && $migration->withinTransaction
            ? $connection->transaction($callback)
            : $callback();
    }

    /**
     * Pretend to run the migrations.
     *
     * @param object $migration
     * @param string $method
     * @return void
     */
    protected function pretendToRun(object $migration, string $method): void
    {
        foreach ($this->getQueries($migration, $method) as $query) {
            $name = get_class($migration);

            Command::info("{$name}: {$query['query']}");
        }
    }

    /**
     * Get all of the queries that would be run for a migration.
     *
     * @param object $migration
     * @param string $method
     * @return array
     */
    protected function getQueries(object $migration, string $method): array
    {
        // Now that we have the connections we can resolve it and pretend to run the
        // queries against the database returning the array of raw SQL statements
        // that would get fired against the database system for this migration.
        $db = $this->resolveConnection(
            $migration->getConnection()
        );

        return $db->pretend(static function () use ($migration, $method) {
            if (method_exists($migration, $method)) {
                $migration->{$method}();
            }
        });
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param string $file
     * @return object
     */
    public function resolve(string $file)
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class;
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param string|array $paths
     * @return array
     */
    public function getMigrationFiles($paths): array
    {
        return Collection::make($paths)->flatMap(function ($path) {
            return Str::endsWith($path, '.php') ? [$path] : $this->files->glob($path . '/*_*.php');
        })->filter()->values()->keyBy(function ($file) {
            return $this->getMigrationName($file);
        })->sortBy(function ($file, $key) {
            return $key;
        })->all();
    }

    /**
     * Require in all the migration files in a given path.
     *
     * @param array $files
     * @return void
     */
    public function requireFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    /**
     * Get the name of the migration.
     *
     * @param string $path
     * @return string
     */
    public function getMigrationName(string $path): string
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Register a custom migration path.
     *
     * @param string $path
     * @return void
     */
    public function path(string $path): void
    {
        $this->paths = array_unique(array_merge($this->paths, [$path]));
    }

    /**
     * Get all of the custom migration paths.
     *
     * @return array
     */
    public function paths(): array
    {
        return $this->paths;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * Execute the given callback using the given connection as the default connection.
     *
     * @param string $name
     * @param callable $callback
     * @return mixed
     */
    public function usingConnection(string $name, callable $callback)
    {
        $previousConnection = $this->resolver->getDefaultConnection();

        $this->setConnection($name);

        return tap($callback(), function () use ($previousConnection) {
            $this->setConnection($previousConnection);
        });
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return void
     */
    public function setConnection(string $name): void
    {
        if (!is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Resolve the database connection instance.
     *
     * @param string $connection
     * @return ConnectionInterface
     */
    public function resolveConnection(string $connection): ConnectionInterface
    {
        return $this->resolver->connection($connection ?: $this->connection);
    }

    /**
     * Get the schema grammar out of a migration connection.
     *
     * @param Connection $connection
     * @return Grammar
     */
    protected function getSchemaGrammar(Connection $connection): Grammar
    {
        if (is_null($grammar = $connection->getSchemaGrammar())) {
            $connection->useDefaultSchemaGrammar();

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    /**
     * Get the migration repository instance.
     *
     * @return MigrationRepositoryInterface
     */
    public function getRepository(): MigrationRepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists(): bool
    {
        return $this->repository->repositoryExists();
    }

    /**
     * Get the file system instance.
     *
<<<<<<< HEAD
     * @return \Mini\Filesystem\Filesystem
=======
     * @return Filesystem
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function getFilesystem(): Filesystem
    {
        return $this->files;
    }

    /**
     * Set the output implementation that should be used by the console.
     *
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Write a note to the console's output.
     *
     * @param string $message
     * @return void
     */
    protected function note(string $message): void
    {
        if ($this->output) {
            $this->output->writeln($message);
        } else {
            Command::info($message);
        }
    }

    /**
     * Fire the given event for the migration.
     *
     * @param mixed $event
     * @return void
     */
    public function fireMigrationEvent($event): void
    {
        if ($this->events) {
            $this->events->dispatch($event);
        }
    }
}
