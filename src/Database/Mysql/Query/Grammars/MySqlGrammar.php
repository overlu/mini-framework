<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query\Grammars;

use Mini\Database\Mysql\Query\Builder;
use Mini\Support\Str;

class MySqlGrammar extends Grammar
{
    /**
     * The grammar specific operators.
     *
     * @var array
     */
    protected array $operators = ['sounds like'];

    /**
     * Add a "where null" clause to the query.
     *
<<<<<<< HEAD
     * @param string|array $columns
     * @param string $boolean
     * @param bool $not
     * @return $this
=======
     * @param Builder $query
     * @param array $where
     * @return string
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function whereNull(Builder $query, array $where): string
    {
        if ($this->isJsonSelector($where['column'])) {
            [$field, $path] = $this->wrapJsonFieldAndPath($where['column']);

            return '(json_extract(' . $field . $path . ') is null OR json_type(json_extract(' . $field . $path . ')) = \'NULL\')';
        }

        return parent::whereNull($query, $where);
    }

    /**
     * Add a "where not null" clause to the query.
     *
<<<<<<< HEAD
     * @param string|array $columns
     * @param string $boolean
     * @return $this
=======
     * @param Builder $query
     * @param array $where
     * @return string
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function whereNotNull(Builder $query, array $where): string
    {
        if ($this->isJsonSelector($where['column'])) {
            [$field, $path] = $this->wrapJsonFieldAndPath($where['column']);

            return '(json_extract(' . $field . $path . ') is not null AND json_type(json_extract(' . $field . $path . ')) != \'NULL\')';
        }

        return parent::whereNotNull($query, $where);
    }

    /**
     * Compile an insert ignore statement into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $values
     * @return string
     */
    public function compileInsertOrIgnore(Builder $query, array $values): string
    {
        return Str::replaceFirst('insert', 'insert ignore', $this->compileInsert($query, $values));
    }

    /**
     * Compile a "JSON contains" statement into SQL.
     *
     * @param string $column
     * @param string $value
     * @return string
     */
    protected function compileJsonContains(string $column, string $value): string
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        return 'json_contains(' . $field . ', ' . $value . $path . ')';
    }

    /**
     * Compile a "JSON length" statement into SQL.
     *
     * @param string $column
     * @param string $operator
     * @param string $value
     * @return string
     */
    protected function compileJsonLength(string $column, string $operator, string $value): string
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        return 'json_length(' . $field . $path . ') ' . $operator . ' ' . $value;
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param string $seed
     * @return string
     */
    public function compileRandom(string $seed): string
    {
        return 'RAND(' . $seed . ')';
    }

    /**
     * Compile the lock into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param bool|string $value
     * @return string
     */
    protected function compileLock(Builder $query, $value): string
    {
        if (!is_string($value)) {
            return $value ? 'for update' : 'lock in share mode';
        }

        return $value;
    }

    /**
     * Compile an insert statement into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $values
     * @return string
     */
    public function compileInsert(Builder $query, array $values): string
    {
        if (empty($values)) {
            $values = [[]];
        }

        return parent::compileInsert($query, $values);
    }

    /**
     * Compile the columns for an update statement.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $values
     * @return string
     */
    protected function compileUpdateColumns(Builder $query, array $values): string
    {
        return collect($values)->map(function ($value, $key) {
            if ($this->isJsonSelector($key)) {
                return $this->compileJsonUpdateColumn($key, $value);
            }

            return $this->wrap($key) . ' = ' . $this->parameter($value);
        })->implode(', ');
    }

    /**
     * Prepare a JSON column being updated using the JSON_SET function.
     *
     * @param string $key
     * @param mixed $value
     * @return string
     */
    protected function compileJsonUpdateColumn(string $key, $value): string
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $value = 'cast(? as json)';
        } else {
            $value = $this->parameter($value);
        }

        [$field, $path] = $this->wrapJsonFieldAndPath($key);

        return "{$field} = json_set({$field}{$path}, {$value})";
    }

    /**
     * Compile an update statement without joins into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $table
     * @param string $columns
     * @param string $where
     * @return string
     */
    protected function compileUpdateWithoutJoins(Builder $query, string $table, string $columns, string $where): string
    {
        $sql = parent::compileUpdateWithoutJoins($query, $table, $columns, $where);

        if (!empty($query->orders)) {
            $sql .= ' ' . $this->compileOrders($query, $query->orders);
        }

        if (isset($query->limit)) {
            $sql .= ' ' . $this->compileLimit($query, $query->limit);
        }

        return $sql;
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * Booleans, integers, and doubles are inserted into JSON updates as raw values.
     *
     * @param array $bindings
     * @param array $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values): array
    {
        $values = collect($values)->reject(function ($value, $column) {
            return $this->isJsonSelector($column) && is_bool($value);
        })->map(static function ($value) {
            return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        })->all();

        return parent::prepareBindingsForUpdate($bindings, $values);
    }

    /**
     * Compile a delete query that does not use joins.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $table
     * @param string $where
     * @return string
     */
    protected function compileDeleteWithoutJoins(Builder $query, string $table, string $where): string
    {
        $sql = parent::compileDeleteWithoutJoins($query, $table, $where);

        // When using MySQL, delete statements may contain order by statements and limits
        // so we will compile both of those here. Once we have finished compiling this
        // we will return the completed SQL statement so it will be executed for us.
        if (!empty($query->orders)) {
            $sql .= ' ' . $this->compileOrders($query, $query->orders);
        }

        if (isset($query->limit)) {
            $sql .= ' ' . $this->compileLimit($query, $query->limit);
        }

        return $sql;
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param string $value
     * @return string
     */
    protected function wrapValue(string $value): string
    {
        return $value === '*' ? $value : '`' . str_replace('`', '``', $value) . '`';
    }

    /**
     * Wrap the given JSON selector.
     *
     * @param string $value
     * @return string
     */
    protected function wrapJsonSelector(string $value): string
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        return 'json_unquote(json_extract(' . $field . $path . '))';
    }

    /**
     * Wrap the given JSON selector for boolean values.
     *
     * @param string $value
     * @return string
     */
    protected function wrapJsonBooleanSelector(string $value): string
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        return 'json_extract(' . $field . $path . ')';
    }
}
