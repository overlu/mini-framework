<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query\Grammars;

use Mini\Database\Mysql\Query\Builder;
use Mini\Database\Mysql\Query\Expression;
use Mini\Support\Arr;
use Mini\Support\Str;

class SqlServerGrammar extends Grammar
{
    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!=',
        'like', 'not like', 'ilike',
        '&', '&=', '|', '|=', '^', '^=',
    ];

    /**
     * Compile a select query into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    public function compileSelect(Builder $query): string
    {
        if (!$query->offset) {
            return parent::compileSelect($query);
        }

        // If an offset is present on the query, we will need to wrap the query in
        // a big "ANSI" offset syntax block. This is very nasty compared to the
        // other database systems but is necessary for implementing features.
        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        return $this->compileAnsiOffset(
            $query, $this->compileComponents($query)
        );
    }

    /**
     * Compile the "select *" portion of the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, array $columns): ?string
    {
        if (!is_null($query->aggregate)) {
<<<<<<< HEAD
            return;
=======
            return null;
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
        }

        $select = $query->distinct ? 'select distinct ' : 'select ';

        // If there is a limit on the query, but not an offset, we will add the top
        // clause to the query, which serves as a "limit" type clause within the
        // SQL Server system similar to the limit keywords available in MySQL.
        if ($query->limit > 0 && $query->offset <= 0) {
            $select .= 'top ' . $query->limit . ' ';
        }

        return $select . $this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $table
     * @return string
     */
    protected function compileFrom(Builder $query, string $table): string
    {
        $from = parent::compileFrom($query, $table);

        if (is_string($query->lock)) {
            return $from . ' ' . $query->lock;
        }

        if (!is_null($query->lock)) {
            return $from . ' with(rowlock,' . ($query->lock ? 'updlock,' : '') . 'holdlock)';
        }

        return $from;
    }

    /**
     * Compile a "where date" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereDate(Builder $query, array $where): string
    {
        $value = $this->parameter($where['value']);

        return 'cast(' . $this->wrap($where['column']) . ' as date) ' . $where['operator'] . ' ' . $value;
    }

    /**
     * Compile a "where time" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereTime(Builder $query, array $where): string
    {
        $value = $this->parameter($where['value']);

        return 'cast(' . $this->wrap($where['column']) . ' as time) ' . $where['operator'] . ' ' . $value;
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

        return $value . ' in (select [value] from openjson(' . $field . $path . '))';
    }

    /**
     * Prepare the binding for a "JSON contains" statement.
     *
     * @param mixed $binding
     * @return string
     */
    public function prepareBindingForJsonContains($binding): string
    {
        return is_bool($binding) ? json_encode($binding, JSON_UNESCAPED_UNICODE) : $binding;
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

        return '(select count(*) from openjson(' . $field . $path . ')) ' . $operator . ' ' . $value;
    }

    /**
     * Create a full ANSI offset clause for the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $components
     * @return string
     */
    protected function compileAnsiOffset(Builder $query, array $components): string
    {
        // An ORDER BY clause is required to make this offset query work, so if one does
        // not exist we'll just create a dummy clause to trick the database and so it
        // does not complain about the queries for not having an "order by" clause.
        if (empty($components['orders'])) {
            $components['orders'] = 'order by (select 0)';
        }

        // We need to add the row number to the query so we can compare it to the offset
        // and limit values given for the statements. So we will add an expression to
        // the "select" that will give back the row numbers on each of the records.
        $components['columns'] .= $this->compileOver($components['orders']);

        unset($components['orders']);

        // Next we need to calculate the constraints that should be placed on the query
        // to get the right offset and limit from our query but if there is no limit
        // set we will just handle the offset only since that is all that matters.
        $sql = $this->concatenate($components);

        return $this->compileTableExpression($sql, $query);
    }

    /**
     * Compile the over statement for a table expression.
     *
     * @param string $orderings
     * @return string
     */
    protected function compileOver(string $orderings): string
    {
        return ", row_number() over ({$orderings}) as row_num";
    }

    /**
     * Compile a common table expression for a query.
     *
     * @param string $sql
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function compileTableExpression(string $sql, Builder $query): string
    {
        $constraint = $this->compileRowConstraint($query);

        return "select * from ({$sql}) as temp_table where row_num {$constraint} order by row_num";
    }

    /**
     * Compile the limit / offset row constraint for a query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function compileRowConstraint(Builder $query): string
    {
        $start = $query->offset + 1;

        if ($query->limit > 0) {
            $finish = $query->offset + $query->limit;

            return "between {$start} and {$finish}";
        }

        return ">= {$start}";
    }

    /**
     * Compile a delete statement without joins into SQL.
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

        return !is_null($query->limit) && $query->limit > 0 && $query->offset <= 0
            ? Str::replaceFirst('delete', 'delete top (' . $query->limit . ')', $sql)
            : $sql;
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param string $seed
     * @return string
     */
    public function compileRandom(string $seed): string
    {
        return 'NEWID()';
    }

    /**
     * Compile the "limit" portions of the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param int $limit
     * @return string
     */
    protected function compileLimit(Builder $query, int $limit): string
    {
        return '';
    }

    /**
     * Compile the "offset" portions of the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param int $offset
     * @return string
     */
    protected function compileOffset(Builder $query, int $offset): string
    {
        return '';
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
        return 'select * from (' . $sql . ') as ' . $this->wrapTable('temp_table');
    }

    /**
     * Compile an exists statement into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    public function compileExists(Builder $query): string
    {
        $existsQuery = clone $query;

        $existsQuery->columns = [];

        return $this->compileSelect($existsQuery->selectRaw('1 [exists]')->limit(1));
    }

    /**
     * Compile an update statement with joins into SQL.
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
    protected function compileUpdateWithJoins(Builder $query, string $table, string $columns, string $where): string
    {
        $alias = last(explode(' as ', $table));

        $joins = $this->compileJoins($query, $query->joins);

        return "update {$alias} set {$columns} from {$table} {$joins} {$where}";
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
        $cleanBindings = Arr::except($bindings, 'select');

        return array_values(
            array_merge($values, Arr::flatten($cleanBindings))
        );
    }

    /**
     * Compile the SQL statement to define a savepoint.
     *
     * @param string $name
     * @return string
     */
    public function compileSavepoint(string $name): string
    {
        return 'SAVE TRANSACTION ' . $name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * @param string $name
     * @return string
     */
    public function compileSavepointRollBack(string $name): string
    {
        return 'ROLLBACK TRANSACTION ' . $name;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s.v';
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param string $value
     * @return string
     */
    protected function wrapValue(string $value): string
    {
        return $value === '*' ? $value : '[' . str_replace(']', ']]', $value) . ']';
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

        return 'json_value(' . $field . $path . ')';
    }

    /**
     * Wrap the given JSON boolean value.
     *
     * @param string $value
     * @return string
     */
    protected function wrapJsonBooleanValue(string $value): string
    {
        return "'" . $value . "'";
    }

    /**
     * Wrap a table in keyword identifiers.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Expression|string $table
=======
     * @param Expression|string $table
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    public function wrapTable($table): string
    {
        if (!$this->isExpression($table)) {
            return $this->wrapTableValuedFunction(parent::wrapTable($table));
        }

        return $this->getValue($table);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param string $table
     * @return string
     */
    protected function wrapTableValuedFunction(string $table): string
    {
        if (preg_match('/^(.+?)(\(.*?\))]$/', $table, $matches) === 1) {
            $table = $matches[1] . ']' . $matches[2];
        }

        return $table;
    }
}
