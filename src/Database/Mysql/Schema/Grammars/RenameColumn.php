<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema\Grammars;

use Doctrine\DBAL\Schema\AbstractSchemaManager as SchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\TableDiff;
use Mini\Database\Mysql\Connection;
use Mini\Database\Mysql\Schema\Blueprint;
use Mini\Support\Fluent;

class RenameColumn
{
    /**
     * Compile a rename column command.
     *
     * @param Grammar $grammar
     * @param Blueprint $blueprint
     * @param Fluent $command
     * @param Connection $connection
     * @return array
     */
    public static function compile(Grammar $grammar, Blueprint $blueprint, Fluent $command, Connection $connection): array
    {
        $schema = $connection->getDoctrineSchemaManager();
        $databasePlatform = $schema->getDatabasePlatform();
        $databasePlatform->registerDoctrineTypeMapping('enum', 'string');

        $column = $connection->getDoctrineColumn(
            $grammar->getTablePrefix() . $blueprint->getTable(), $command->from
        );

        return (array)$databasePlatform->getAlterTableSQL(static::getRenamedDiff(
            $grammar, $blueprint, $command, $column, $schema
        ));
    }

    /**
     * Get a new column instance with the new column name.
     *
     * @param Grammar $grammar
     * @param Blueprint $blueprint
     * @param Fluent $command
     * @param Column $column
     * @param SchemaManager $schema
     * @return TableDiff
     */
    protected static function getRenamedDiff(Grammar $grammar, Blueprint $blueprint, Fluent $command, Column $column, SchemaManager $schema): TableDiff
    {
        return static::setRenamedColumns(
            $grammar->getDoctrineTableDiff($blueprint, $schema), $command, $column
        );
    }

    /**
     * Set the renamed columns on the table diff.
     *
     * @param TableDiff $tableDiff
     * @param Fluent $command
     * @param Column $column
     * @return TableDiff
     */
    protected static function setRenamedColumns(TableDiff $tableDiff, Fluent $command, Column $column): TableDiff
    {
        $tableDiff->renamedColumns = [
            $command->from => new Column($command->to, $column->getType(), $column->toArray()),
        ];

        return $tableDiff;
    }
}
