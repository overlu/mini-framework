<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query\Grammars;

use Mini\Database\Mysql\Grammar as BaseGrammar;
use Mini\Database\Mysql\Query\Builder;
use Mini\Database\Mysql\Query\Expression;
use Mini\Database\Mysql\Query\JoinClause;
use Mini\Support\Arr;
use Mini\Support\Str;
use RuntimeException;

class Grammar extends BaseGrammar
{
    /**
     * The grammar specific operators.
     *
     * @var array
     */
    protected array $operators = [];

    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected array $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
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
        if ($query->unions && $query->aggregate) {
            return $this->compileUnionAggregate($query);
        }

        // If the query does not have any columns set, we'll set the columns to the
        // * character to just get all of the columns from the database. Then we
        // can build the query and concatenate all the pieces together as one.
        $original = $query->columns;

        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.
        $sql = trim($this->concatenate(
            $this->compileComponents($query))
        );

        if ($query->unions) {
            $sql = $this->wrapUnion($sql) . ' ' . $this->compileUnions($query);
        }

        $query->columns = $original;

        return $sql;
    }

    /**
     * Compile the components necessary for a select clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    protected function compileComponents(Builder $query): array
    {
        $sql = [];

        foreach ($this->selectComponents as $component) {
            if (isset($query->$component)) {
                $method = 'compile' . ucfirst($component);

                $sql[$component] = $this->$method($query, $query->$component);
            }
        }

        return $sql;
    }

    /**
     * Compile an aggregated select clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, array $aggregate): string
    {
        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if (is_array($query->distinct)) {
            $column = 'distinct ' . $this->columnize($query->distinct);
        } elseif ($query->distinct && $column !== '*') {
            $column = 'distinct ' . $column;
        }

        return 'select ' . $aggregate['function'] . '(' . $column . ') as aggregate';
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
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if (!is_null($query->aggregate)) {
<<<<<<< HEAD
            return;
=======
            return null;
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
        }

        if ($query->distinct) {
            $select = 'select distinct ';
        } else {
            $select = 'select ';
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
        return 'from ' . $this->wrapTable($table);
    }

    /**
     * Compile the "join" portions of the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $joins
     * @return string
     */
    protected function compileJoins(Builder $query, array $joins): string
    {
        return collect($joins)->map(function ($join) use ($query) {
            $table = $this->wrapTable($join->table);

            $nestedJoins = is_null($join->joins) ? '' : ' ' . $this->compileJoins($query, $join->joins);

            $tableAndNestedJoins = is_null($join->joins) ? $table : '(' . $table . $nestedJoins . ')';

            return trim("{$join->type} join {$tableAndNestedJoins} {$this->compileWheres($join)}");
        })->implode(' ');
    }

    /**
     * Compile the "where" portions of the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function compileWheres(Builder $query): string
    {
        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
        if (is_null($query->wheres)) {
            return '';
        }

        // If we actually have some where clauses, we will strip off the first boolean
        // operator, which is added by the query builders for convenience so we can
        // avoid checking for the first clauses in each of the compilers methods.
        if (count($sql = $this->compileWheresToArray($query)) > 0) {
            return $this->concatenateWhereClauses($query, $sql);
        }

        return '';
    }

    /**
     * Get an array of all the where clauses for the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    protected function compileWheresToArray(Builder $query): array
    {
        return collect($query->wheres)->map(function ($where) use ($query) {
            return $where['boolean'] . ' ' . $this->{"where{$where['type']}"}($query, $where);
        })->all();
    }

    /**
     * Format the where clause statements into one string.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $sql
     * @return string
     */
    protected function concatenateWhereClauses(Builder $query, array $sql): string
    {
        $conjunction = $query instanceof JoinClause ? 'on' : 'where';

        return $conjunction . ' ' . $this->removeLeadingBoolean(implode(' ', $sql));
    }

    /**
     * Compile a raw where clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereRaw(Builder $query, array $where): string
    {
        return $where['sql'];
    }

    /**
     * Compile a basic where clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereBasic(Builder $query, array $where): string
    {
        $value = $this->parameter($where['value']);

        return $this->wrap($where['column']) . ' ' . $where['operator'] . ' ' . $value;
    }

    /**
     * Compile a "where in" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereIn(Builder $query, array $where): string
    {
        if (!empty($where['values'])) {
            return $this->wrap($where['column']) . ' in (' . $this->parameterize($where['values']) . ')';
        }

        return '0 = 1';
    }

    /**
     * Compile a "where not in" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereNotIn(Builder $query, array $where): string
    {
        if (!empty($where['values'])) {
            return $this->wrap($where['column']) . ' not in (' . $this->parameterize($where['values']) . ')';
        }

        return '1 = 1';
    }

    /**
     * Compile a "where not in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereNotInRaw(Builder $query, array $where): string
    {
        if (!empty($where['values'])) {
            return $this->wrap($where['column']) . ' not in (' . implode(', ', $where['values']) . ')';
        }

        return '1 = 1';
    }

    /**
     * Compile a "where in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereInRaw(Builder $query, array $where): string
    {
        if (!empty($where['values'])) {
            return $this->wrap($where['column']) . ' in (' . implode(', ', $where['values']) . ')';
        }

        return '0 = 1';
    }

    /**
     * Compile a "where null" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereNull(Builder $query, array $where): string
    {
        return $this->wrap($where['column']) . ' is null';
    }

    /**
     * Compile a "where not null" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereNotNull(Builder $query, array $where): string
    {
        return $this->wrap($where['column']) . ' is not null';
    }

    /**
     * Compile a "between" where clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereBetween(Builder $query, array $where): string
    {
        $between = $where['not'] ? 'not between' : 'between';

        $min = $this->parameter(reset($where['values']));

        $max = $this->parameter(end($where['values']));

        return $this->wrap($where['column']) . ' ' . $between . ' ' . $min . ' and ' . $max;
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
        return $this->dateBasedWhere('date', $query, $where);
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
        return $this->dateBasedWhere('time', $query, $where);
    }

    /**
     * Compile a "where day" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereDay(Builder $query, array $where): string
    {
        return $this->dateBasedWhere('day', $query, $where);
    }

    /**
     * Compile a "where month" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereMonth(Builder $query, array $where): string
    {
        return $this->dateBasedWhere('month', $query, $where);
    }

    /**
     * Compile a "where year" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereYear(Builder $query, array $where): string
    {
        return $this->dateBasedWhere('year', $query, $where);
    }

    /**
     * Compile a date based where clause.
     *
     * @param string $type
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function dateBasedWhere(string $type, Builder $query, array $where): string
    {
        $value = $this->parameter($where['value']);

        return $type . '(' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }

    /**
     * Compile a where clause comparing two columns..
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereColumn(Builder $query, array $where): string
    {
        return $this->wrap($where['first']) . ' ' . $where['operator'] . ' ' . $this->wrap($where['second']);
    }

    /**
     * Compile a nested where clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereNested(Builder $query, array $where): string
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query we need to take the leading "where" of queries.
        $offset = $query instanceof JoinClause ? 3 : 6;

        return '(' . substr($this->compileWheres($where['query']), $offset) . ')';
    }

    /**
     * Compile a where condition with a sub-select.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereSub(Builder $query, array $where): string
    {
        $select = $this->compileSelect($where['query']);

        return $this->wrap($where['column']) . ' ' . $where['operator'] . " ($select)";
    }

    /**
     * Compile a where exists clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereExists(Builder $query, array $where): string
    {
        return 'exists (' . $this->compileSelect($where['query']) . ')';
    }

    /**
     * Compile a where exists clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereNotExists(Builder $query, array $where): string
    {
        return 'not exists (' . $this->compileSelect($where['query']) . ')';
    }

    /**
     * Compile a where row values condition.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereRowValues(Builder $query, array $where): string
    {
        $columns = $this->columnize($where['columns']);

        $values = $this->parameterize($where['values']);

        return '(' . $columns . ') ' . $where['operator'] . ' (' . $values . ')';
    }

    /**
     * Compile a "where JSON boolean" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereJsonBoolean(Builder $query, array $where): string
    {
        $column = $this->wrapJsonBooleanSelector($where['column']);

        $value = $this->wrapJsonBooleanValue(
            $this->parameter($where['value'])
        );

        return $column . ' ' . $where['operator'] . ' ' . $value;
    }

    /**
     * Compile a "where JSON contains" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereJsonContains(Builder $query, array $where): string
    {
        $not = $where['not'] ? 'not ' : '';

        return $not . $this->compileJsonContains(
                $where['column'], $this->parameter($where['value'])
            );
    }

    /**
     * Compile a "JSON contains" statement into SQL.
     *
     * @param string $column
     * @param string $value
     * @return string
     *
     * @throws RuntimeException
     */
    protected function compileJsonContains(string $column, string $value): string
    {
        throw new RuntimeException('This database engine does not support JSON contains operations.');
    }

    /**
     * Prepare the binding for a "JSON contains" statement.
     *
     * @param mixed $binding
     * @return string
     */
    public function prepareBindingForJsonContains($binding): string
    {
        return json_encode($binding, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Compile a "where JSON length" clause.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $where
     * @return string
     */
    protected function whereJsonLength(Builder $query, array $where): string
    {
        return $this->compileJsonLength(
            $where['column'], $where['operator'], $this->parameter($where['value'])
        );
    }

    /**
     * Compile a "JSON length" statement into SQL.
     *
     * @param string $column
     * @param string $operator
     * @param string $value
     * @return string
     *
     * @throws RuntimeException
     */
    protected function compileJsonLength(string $column, string $operator, string $value): string
    {
        throw new RuntimeException('This database engine does not support JSON length operations.');
    }

    /**
     * Compile the "group by" portions of the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $groups
     * @return string
     */
    protected function compileGroups(Builder $query, array $groups): string
    {
        return 'group by ' . $this->columnize($groups);
    }

    /**
     * Compile the "having" portions of the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $havings
     * @return string
     */
    protected function compileHavings(Builder $query, array $havings): string
    {
        $sql = implode(' ', array_map([$this, 'compileHaving'], $havings));

        return 'having ' . $this->removeLeadingBoolean($sql);
    }

    /**
     * Compile a single having clause.
     *
     * @param array $having
     * @return string
     */
    protected function compileHaving(array $having): string
    {
        // If the having clause is "raw", we can just return the clause straight away
        // without doing any more processing on it. Otherwise, we will compile the
        // clause into SQL based on the components that make it up from builder.
        if ($having['type'] === 'Raw') {
            return $having['boolean'] . ' ' . $having['sql'];
        } elseif ($having['type'] === 'between') {
            return $this->compileHavingBetween($having);
        }

        return $this->compileBasicHaving($having);
    }

    /**
     * Compile a basic having clause.
     *
     * @param array $having
     * @return string
     */
    protected function compileBasicHaving(array $having): string
    {
        $column = $this->wrap($having['column']);

        $parameter = $this->parameter($having['value']);

        return $having['boolean'] . ' ' . $column . ' ' . $having['operator'] . ' ' . $parameter;
    }

    /**
     * Compile a "between" having clause.
     *
     * @param array $having
     * @return string
     */
    protected function compileHavingBetween(array $having): string
    {
        $between = $having['not'] ? 'not between' : 'between';

        $column = $this->wrap($having['column']);

        $min = $this->parameter(head($having['values']));

        $max = $this->parameter(last($having['values']));

        return $having['boolean'] . ' ' . $column . ' ' . $between . ' ' . $min . ' and ' . $max;
    }

    /**
     * Compile the "order by" portions of the query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $orders
     * @return string
     */
    protected function compileOrders(Builder $query, array $orders): string
    {
        if (!empty($orders)) {
            return 'order by ' . implode(', ', $this->compileOrdersToArray($query, $orders));
        }

        return '';
    }

    /**
     * Compile the query orders to an array.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $orders
     * @return array
     */
    protected function compileOrdersToArray(Builder $query, array $orders): array
    {
        return array_map(function ($order) {
            return $order['sql'] ?? $this->wrap($order['column']) . ' ' . $order['direction'];
        }, $orders);
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param string $seed
     * @return string
     */
    public function compileRandom(string $seed): string
    {
        return 'RANDOM()';
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
<<<<<<< HEAD
        return 'limit ' . (int)$limit;
=======
        return 'limit ' . $limit;
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
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
<<<<<<< HEAD
        return 'offset ' . (int)$offset;
=======
        return 'offset ' . $offset;
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
    }

    /**
     * Compile the "union" queries attached to the main query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function compileUnions(Builder $query): string
    {
        $sql = '';

        foreach ($query->unions as $union) {
            $sql .= $this->compileUnion($union);
        }

        if (!empty($query->unionOrders)) {
            $sql .= ' ' . $this->compileOrders($query, $query->unionOrders);
        }

        if (isset($query->unionLimit)) {
            $sql .= ' ' . $this->compileLimit($query, $query->unionLimit);
        }

        if (isset($query->unionOffset)) {
            $sql .= ' ' . $this->compileOffset($query, $query->unionOffset);
        }

        return ltrim($sql);
    }

    /**
     * Compile a single union statement.
     *
     * @param array $union
     * @return string
     */
    protected function compileUnion(array $union): string
    {
        $conjunction = $union['all'] ? ' union all ' : ' union ';

        return $conjunction . $this->wrapUnion($union['query']->toSql());
    }

    /**
     * Wrap a union subquery in parentheses.
     *
     * @param string $sql
     * @return string
     */
    protected function wrapUnion(string $sql): string
    {
        return '(' . $sql . ')';
    }

    /**
     * Compile a union aggregate query into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function compileUnionAggregate(Builder $query): string
    {
        $sql = $this->compileAggregate($query, $query->aggregate);

        $query->aggregate = null;

        return $sql . ' from (' . $this->compileSelect($query) . ') as ' . $this->wrapTable('temp_table');
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
        $select = $this->compileSelect($query);

        return "select exists({$select}) as {$this->wrap('exists')}";
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
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);

        if (empty($values)) {
            return "insert into {$table} default values";
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $columns = $this->columnize(array_keys(reset($values)));

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same amount of parameter
        // bindings so we will loop through the record and parameterize them all.
        $parameters = collect($values)->map(function ($record) {
            return '(' . $this->parameterize($record) . ')';
        })->implode(', ');

        return "insert into $table ($columns) values $parameters";
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
     *
     * @throws RuntimeException
     */
    public function compileInsertOrIgnore(Builder $query, array $values): string
    {
        throw new RuntimeException('This database engine does not support inserting while ignoring errors.');
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $values
     * @param string $sequence
     * @return string
     */
    public function compileInsertGetId(Builder $query, array $values, string $sequence): string
    {
        return $this->compileInsert($query, $values);
    }

    /**
     * Compile an insert statement using a subquery into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $columns
     * @param string $sql
     * @return string
     */
    public function compileInsertUsing(Builder $query, array $columns, string $sql): string
    {
        return "insert into {$this->wrapTable($query->from)} ({$this->columnize($columns)}) $sql";
    }

    /**
     * Compile an update statement into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $values
     * @return string
     */
    public function compileUpdate(Builder $query, array $values): string
    {
        $table = $this->wrapTable($query->from);

        $columns = $this->compileUpdateColumns($query, $values);

        $where = $this->compileWheres($query);

        return trim(
            isset($query->joins)
                ? $this->compileUpdateWithJoins($query, $table, $columns, $where)
                : $this->compileUpdateWithoutJoins($query, $table, $columns, $where)
        );
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
            return $this->wrap($key) . ' = ' . $this->parameter($value);
        })->implode(', ');
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
        return "update {$table} set {$columns} {$where}";
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
        $joins = $this->compileJoins($query, $query->joins);

        return "update {$table} {$joins} set {$columns} {$where}";
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
        $cleanBindings = Arr::except($bindings, ['select', 'join']);

        return array_values(
            array_merge($bindings['join'], $values, Arr::flatten($cleanBindings))
        );
    }

    /**
     * Compile a delete statement into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    public function compileDelete(Builder $query): string
    {
        $table = $this->wrapTable($query->from);

        $where = $this->compileWheres($query);

        return trim(
            isset($query->joins)
                ? $this->compileDeleteWithJoins($query, $table, $where)
                : $this->compileDeleteWithoutJoins($query, $table, $where)
        );
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
        return "delete from {$table} {$where}";
    }

    /**
     * Compile a delete statement with joins into SQL.
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
    protected function compileDeleteWithJoins(Builder $query, string $table, string $where): string
    {
        $alias = last(explode(' as ', $table));

        $joins = $this->compileJoins($query, $query->joins);

        return "delete {$alias} from {$table} {$joins} {$where}";
    }

    /**
     * Prepare the bindings for a delete statement.
     *
     * @param array $bindings
     * @return array
     */
    public function prepareBindingsForDelete(array $bindings): array
    {
        return Arr::flatten(
            Arr::except($bindings, 'select')
        );
    }

    /**
     * Compile a truncate table statement into SQL.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    public function compileTruncate(Builder $query): array
    {
        return ['truncate table ' . $this->wrapTable($query->from) => []];
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
    protected function compileLock(Builder $query, string $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * Determine if the grammar supports savepoints.
     *
     * @return bool
     */
    public function supportsSavepoints(): bool
    {
        return true;
    }

    /**
     * Compile the SQL statement to define a savepoint.
     *
     * @param string $name
     * @return string
     */
    public function compileSavepoint(string $name): string
    {
        return 'SAVEPOINT ' . $name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * @param string $name
     * @return string
     */
    public function compileSavepointRollBack(string $name): string
    {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

    /**
     * Wrap a value in keyword identifiers.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Expression|string $value
=======
     * @param Expression|string $value
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param bool $prefixAlias
     * @return string
     */
    public function wrap($value, bool $prefixAlias = false): string
    {
        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }

        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on its
        // own, and then join these both back together using the "as" connector.
        if (stripos($value, ' as ') !== false) {
            return $this->wrapAliasedValue($value, $prefixAlias);
        }

        // If the given value is a JSON selector we will wrap it differently than a
        // traditional value. We will need to split this path and wrap each part
        // wrapped, etc. Otherwise, we will simply wrap the value as a string.
        if ($this->isJsonSelector($value)) {
            return $this->wrapJsonSelector($value);
        }

        return $this->wrapSegments(explode('.', $value));
    }

    /**
     * Wrap the given JSON selector.
     *
     * @param string $value
     * @return string
     *
     * @throws RuntimeException
     */
    protected function wrapJsonSelector(string $value): string
    {
        throw new RuntimeException('This database engine does not support JSON operations.');
    }

    /**
     * Wrap the given JSON selector for boolean values.
     *
     * @param string $value
     * @return string
     */
    protected function wrapJsonBooleanSelector(string $value): string
    {
        return $this->wrapJsonSelector($value);
    }

    /**
     * Wrap the given JSON boolean value.
     *
     * @param string $value
     * @return string
     */
    protected function wrapJsonBooleanValue(string $value): string
    {
        return $value;
    }

    /**
     * Split the given JSON selector into the field and the optional path and wrap them separately.
     *
     * @param string $column
     * @return array
     */
    protected function wrapJsonFieldAndPath(string $column): array
    {
        $parts = explode('->', $column, 2);

        $field = $this->wrap($parts[0]);

        $path = count($parts) > 1 ? ', ' . $this->wrapJsonPath($parts[1], '->') : '';

        return [$field, $path];
    }

    /**
     * Wrap the given JSON path.
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    protected function wrapJsonPath(string $value, string $delimiter = '->'): string
    {
        $value = preg_replace("/([\\\\]+)?\\'/", "\\'", $value);

        return '\'$."' . str_replace($delimiter, '"."', $value) . '"\'';
    }

    /**
     * Determine if the given string is a JSON selector.
     *
     * @param string $value
     * @return bool
     */
    protected function isJsonSelector(string $value): bool
    {
        return Str::contains($value, '->');
    }

    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param array $segments
     * @return string
     */
    protected function concatenate(array $segments): string
    {
<<<<<<< HEAD
        return implode(' ', array_filter($segments, function ($value) {
=======
        return implode(' ', array_filter($segments, static function ($value) {
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
            return (string)$value !== '';
        }));
    }

    /**
     * Remove the leading boolean from a statement.
     *
     * @param string $value
     * @return string
     */
    protected function removeLeadingBoolean(string $value): string
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * Get the grammar specific operators.
     *
     * @return array
     */
    public function getOperators(): array
    {
        return $this->operators;
    }
}
