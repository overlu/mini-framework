<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema\Grammars;

use Doctrine\DBAL\Schema\AbstractSchemaManager as SchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Mini\Database\Mysql\Connection;
use Mini\Database\Mysql\Schema\Blueprint;
use Mini\Support\Fluent;
use RuntimeException;

class ChangeColumn
{
    /**
     * Compile a change column command into a series of SQL statements.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Grammars\Grammar $grammar
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $command
     * @param \Mini\Database\Mysql\Connection $connection
=======
     * @param Grammar $grammar
     * @param Blueprint $blueprint
     * @param Fluent $command
     * @param Connection $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     *
     * @throws RuntimeException
     */
    public static function compile(Grammar $grammar, Blueprint $blueprint, Fluent $command, Connection $connection): array
    {
        if (!$connection->isDoctrineAvailable()) {
            throw new RuntimeException(sprintf(
                'Changing columns for table "%s" requires Doctrine DBAL. Please install the doctrine/dbal package.',
                $blueprint->getTable()
            ));
        }

        $schema = $connection->getDoctrineSchemaManager();
        $databasePlatform = $schema->getDatabasePlatform();
        $databasePlatform->registerDoctrineTypeMapping('enum', 'string');

        $tableDiff = static::getChangedDiff(
            $grammar, $blueprint, $schema
        );

        if ($tableDiff !== false) {
            return (array)$databasePlatform->getAlterTableSQL($tableDiff);
        }

        return [];
    }

    /**
     * Get the Doctrine table difference for the given changes.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Grammars\Grammar $grammar
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager $schema
     * @return \Doctrine\DBAL\Schema\TableDiff|bool
=======
     * @param Grammar $grammar
     * @param Blueprint $blueprint
     * @param SchemaManager $schema
     * @return TableDiff|bool
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected static function getChangedDiff($grammar, Blueprint $blueprint, SchemaManager $schema)
    {
        $current = $schema->listTableDetails($grammar->getTablePrefix() . $blueprint->getTable());

        return (new Comparator)->diffTable(
            $current, static::getTableWithColumnChanges($blueprint, $current)
        );
    }

    /**
     * Get a copy of the given Doctrine table after making the column changes.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Doctrine\DBAL\Schema\Table $table
     * @return \Doctrine\DBAL\Schema\Table
=======
     * @param Blueprint $blueprint
     * @param Table $table
     * @return Table
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected static function getTableWithColumnChanges(Blueprint $blueprint, Table $table): Table
    {
        $table = clone $table;

        foreach ($blueprint->getChangedColumns() as $fluent) {
            $column = static::getDoctrineColumn($table, $fluent);

            // Here we will spin through each fluent column definition and map it to the proper
            // Doctrine column definitions - which is necessary because Laravel and Doctrine
            // use some different terminology for various column attributes on the tables.
            foreach ($fluent->getAttributes() as $key => $value) {
                if (!is_null($option = static::mapFluentOptionToDoctrine($key))) {
                    if (method_exists($column, $method = 'set' . ucfirst($option))) {
                        $column->{$method}(static::mapFluentValueToDoctrine($option, $value));
                        continue;
                    }

                    $column->setCustomSchemaOption($option, static::mapFluentValueToDoctrine($option, $value));
                }
            }
        }

        return $table;
    }

    /**
     * Get the Doctrine column instance for a column change.
     *
<<<<<<< HEAD
     * @param \Doctrine\DBAL\Schema\Table $table
     * @param \Mini\Support\Fluent $fluent
     * @return \Doctrine\DBAL\Schema\Column
=======
     * @param Table $table
     * @param Fluent $fluent
     * @return Column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected static function getDoctrineColumn(Table $table, Fluent $fluent): Column
    {
        return $table->changeColumn(
            $fluent['name'], static::getDoctrineColumnChangeOptions($fluent)
        )->getColumn($fluent['name']);
    }

    /**
     * Get the Doctrine column change options.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $fluent
=======
     * @param Fluent $fluent
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    protected static function getDoctrineColumnChangeOptions(Fluent $fluent): array
    {
        $options = ['type' => static::getDoctrineColumnType($fluent['type'])];

        if (in_array($fluent['type'], ['text', 'mediumText', 'longText'])) {
            $options['length'] = static::calculateDoctrineTextLength($fluent['type']);
        }

        if (static::doesntNeedCharacterOptions($fluent['type'])) {
            $options['customSchemaOptions'] = [
                'collation' => '',
                'charset' => '',
            ];
        }

        return $options;
    }

    /**
     * Get the doctrine column type.
     *
     * @param string $type
<<<<<<< HEAD
     * @return \Doctrine\DBAL\Types\Type
=======
     * @return Type
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected static function getDoctrineColumnType(string $type): Type
    {
        $type = strtolower($type);

        switch ($type) {
            case 'biginteger':
                $type = 'bigint';
                break;
            case 'smallinteger':
                $type = 'smallint';
                break;
            case 'mediumtext':
            case 'longtext':
                $type = 'text';
                break;
            case 'binary':
                $type = 'blob';
                break;
            case 'uuid':
                $type = 'guid';
                break;
        }

        return Type::getType($type);
    }

    /**
     * Calculate the proper column length to force the Doctrine text type.
     *
     * @param string $type
     * @return int
     */
    protected static function calculateDoctrineTextLength(string $type): ?int
    {
        switch ($type) {
            case 'mediumText':
                return 65535 + 1;
            case 'longText':
                return 16777215 + 1;
            default:
                return 255 + 1;
        }
    }

    /**
     * Determine if the given type does not need character / collation options.
     *
     * @param string $type
     * @return bool
     */
    protected static function doesntNeedCharacterOptions(string $type): bool
    {
        return in_array($type, [
            'bigInteger',
            'binary',
            'boolean',
            'date',
            'decimal',
            'double',
            'float',
            'integer',
            'json',
            'mediumInteger',
            'smallInteger',
            'time',
            'tinyInteger',
        ]);
    }

    /**
     * Get the matching Doctrine option for a given Fluent attribute name.
     *
     * @param string $attribute
     * @return string|null
     */
    protected static function mapFluentOptionToDoctrine(string $attribute): ?string
    {
        switch ($attribute) {
            case 'type':
            case 'name':
                return null;
            case 'nullable':
                return 'notnull';
            case 'total':
                return 'precision';
            case 'places':
                return 'scale';
            default:
                return $attribute;
        }
    }

    /**
     * Get the matching Doctrine value for a given Fluent attribute.
     *
     * @param string $option
     * @param mixed $value
     * @return mixed
     */
    protected static function mapFluentValueToDoctrine(string $option, $value)
    {
        return $option === 'notnull' ? !$value : $value;
    }
}
