<<<<<<< HEAD
<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Migrations;

class TableGuesser
{
    const CREATE_PATTERNS = [
        '/^create_(\w+)_table$/',
        '/^create_(\w+)$/',
    ];

    const CHANGE_PATTERNS = [
        '/_(to|from|in)_(\w+)_table$/',
        '/_(to|from|in)_(\w+)$/',
    ];

    /**
     * Attempt to guess the table name and "creation" status of the given migration.
     *
     * @param string $migration
     * @return array
     */
    public static function guess($migration)
    {
        foreach (self::CREATE_PATTERNS as $pattern) {
            if (preg_match($pattern, $migration, $matches)) {
                return [$matches[1], $create = true];
            }
        }

        foreach (self::CHANGE_PATTERNS as $pattern) {
            if (preg_match($pattern, $migration, $matches)) {
                return [$matches[2], $create = false];
            }
        }
    }
}
=======
<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Migrations;

class TableGuesser
{
    public const CREATE_PATTERNS = [
        '/^create_(\w+)_table$/',
        '/^create_(\w+)$/',
    ];

    public const CHANGE_PATTERNS = [
        '/_(to|from|in)_(\w+)_table$/',
        '/_(to|from|in)_(\w+)$/',
    ];

    /**
     * Attempt to guess the table name and "creation" status of the given migration.
     *
     * @param string $migration
     * @return array
     */
    public static function guess(string $migration): ?array
    {
        foreach (self::CREATE_PATTERNS as $pattern) {
            if (preg_match($pattern, $migration, $matches)) {
                return [$matches[1], $create = true];
            }
        }

        foreach (self::CHANGE_PATTERNS as $pattern) {
            if (preg_match($pattern, $migration, $matches)) {
                return [$matches[2], $create = false];
            }
        }
    }
}
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
