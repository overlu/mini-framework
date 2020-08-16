<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query\Grammars;

use Mini\Database\Mysql\Query\Builder;
use Mini\Support\Arr;
use Mini\Support\Str;

class SQLiteGrammar extends Grammar
{
    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'ilike',
        '&', '|', '<<', '>>',
    ];

    /**
     * Compile the lock into SQL.
     *
     * @param Builder $query
     * @param bool|string $value
     * @return string
     */
    protected function compileLock(Builder $query, $value): string
    {
        return '';
    }

    /**
     * Wrap a union subquery in parentheses.
     *
     * @param string $sql
     * @return string
     */
    protected function wrapUnion(string $sql): string
    {
        return 'select * from (' . $sql . ')';
    }

    /**
     * Compile a "where date" clause.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function whereDate(Builder $query, array $where): string
    {
        return $this->dateBasedWhere('%Y-%m-%d', $query, $where);
    }

    /**
     * Compile a "where day" clause.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function whereDay(Builder $query, array $where): string
    {
        return $this->dateBasedWhere('%d', $query, $where);
    }

    /**
     * Compile a "where month" clause.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function whereMonth(Builder $query, array $where): string
    {
        return $this->dateBasedWhere('%m', $query, $where);
    }

    /**
     * Compile a "where year" clause.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function whereYear(Builder $query, array $where): string
    {
        return $this->dateBasedWhere('%Y', $query, $where);
    }

    /**
     * Compile a "where time" clause.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function whereTime(Builder $query, array $where): string
    {
        return $this->dateBasedWhere('%H:%M:%S', $query, $where);
    }

    /**
     * Compile a date based where clause.
     *
     * @param string $type
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function dateBasedWhere(string $type, Builder $query, array $where): string
    {
        $value = $this->parameter($where['value']);

        return "strftime('{$type}', {$this->wrap($where['column'])}) {$where['operator']} cast({$value} as text)";
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

        return 'json_array_length(' . $field . $path . ') ' . $operator . ' ' . $value;
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param Builder $query
     * @param array $values
     * @return string
     */
    public function compileUpdate(Builder $query, array $values): string
    {
        if (isset($query->joins) || isset($query->limit)) {
            return $this->compileUpdateWithJoinsOrLimit($query, $values);
        }

        return parent::compileUpdate($query, $values);
    }

    /**
     * Compile an insert ignore statement into SQL.
     *
     * @param Builder $query
     * @param array $values
     * @return string
     */
    public function compileInsertOrIgnore(Builder $query, array $values): string
    {
        return Str::replaceFirst('insert', 'insert or ignore', $this->compileInsert($query, $values));
    }

    /**
     * Compile the columns for an update statement.
     *
     * @param Builder $query
     * @param array $values
     * @return string
     */
    protected function compileUpdateColumns(Builder $query, array $values): string
    {
        $jsonGroups = $this->groupJsonColumnsForUpdate($values);

        return collect($values)->reject(function ($value, $key) {
            return $this->isJsonSelector($key);
        })->merge($jsonGroups)->map(function ($value, $key) use ($jsonGroups) {
            $column = last(explode('.', $key));

            $value = isset($jsonGroups[$key]) ? $this->compileJsonPatch($column, $value) : $this->parameter($value);

            return $this->wrap($column) . ' = ' . $value;
        })->implode(', ');
    }

    /**
     * Group the nested JSON columns.
     *
     * @param array $values
     * @return array
     */
    protected function groupJsonColumnsForUpdate(array $values): array
    {
        $groups = [];

        foreach ($values as $key => $value) {
            if ($this->isJsonSelector($key)) {
                Arr::set($groups, str_replace('->', '.', Str::after($key, '.')), $value);
            }
        }

        return $groups;
    }

    /**
     * Compile a "JSON" patch statement into SQL.
     *
     * @param string $column
     * @param mixed $value
     * @return string
     */
    protected function compileJsonPatch(string $column, $value): string
    {
        return "json_patch(ifnull({$this->wrap($column)}, json('{}')), json({$this->parameter($value)}))";
    }

    /**
     * Compile an update statement with joins or limit into SQL.
     *
     * @param Builder $query
     * @param array $values
     * @return string
     */
    protected function compileUpdateWithJoinsOrLimit(Builder $query, array $values): string
    {
        $table = $this->wrapTable($query->from);

        $columns = $this->compileUpdateColumns($query, $values);

        $alias = last(preg_split('/\s+as\s+/i', $query->from));

        $selectSql = $this->compileSelect($query->select($alias . '.rowid'));

        return "update {$table} set {$columns} where {$this->wrap('rowid')} in ({$selectSql})";
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * @param array $bindings
     * @param array $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values): array
    {
        $groups = $this->groupJsonColumnsForUpdate($values);

        $values = collect($values)->reject(function ($value, $key) {
            return $this->isJsonSelector($key);
        })->merge($groups)->map(static function ($value) {
            return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        })->all();

        $cleanBindings = Arr::except($bindings, 'select');

        return array_values(
            array_merge($values, Arr::flatten($cleanBindings))
        );
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param Builder $query
     * @return string
     */
    public function compileDelete(Builder $query): string
    {
        if (isset($query->joins) || isset($query->limit)) {
            return $this->compileDeleteWithJoinsOrLimit($query);
        }

        return parent::compileDelete($query);
    }

    /**
     * Compile a delete statement with joins or limit into SQL.
     *
     * @param Builder $query
     * @return string
     */
    protected function compileDeleteWithJoinsOrLimit(Builder $query): string
    {
        $table = $this->wrapTable($query->from);

        $alias = last(preg_split('/\s+as\s+/i', $query->from));

        $selectSql = $this->compileSelect($query->select($alias . '.rowid'));

        return "delete from {$table} where {$this->wrap('rowid')} in ({$selectSql})";
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @param Builder $query
     * @return array
     */
    public function compileTruncate(Builder $query): array
    {
        return [
            'delete from sqlite_sequence where name = ?' => [$query->from],
            'delete from ' . $this->wrapTable($query->from) => [],
        ];
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

        return 'json_extract(' . $field . $path . ')';
    }
}
