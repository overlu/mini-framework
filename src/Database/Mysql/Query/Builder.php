<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query;

use BadMethodCallException;
use Closure;
use DateTimeInterface;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Pagination\LengthAwarePaginator;
use Mini\Contracts\Support\Arrayable;
use Mini\Database\Mysql\Concerns\BuildsQueries;
use Mini\Database\Mysql\ConnectionInterface;
use Mini\Database\Mysql\Eloquent\Builder as EloquentBuilder;
use Mini\Database\Mysql\Eloquent\Relations\Relation;
use Mini\Database\Mysql\Query\Grammars\Grammar;
use Mini\Database\Mysql\Query\Processors\Processor;
use Mini\Pagination\Paginator;
use Mini\Support\Arr;
use Mini\Support\Collection;
use Mini\Support\LazyCollection;
use Mini\Support\Str;
use Mini\Support\Traits\ForwardsCalls;
use Mini\Support\Traits\Macroable;
use InvalidArgumentException;
use RuntimeException;

class Builder
{
    use BuildsQueries, ForwardsCalls, Macroable {
        __call as macroCall;
    }

    /**
     * The database connection instance.
     *
     * @var ConnectionInterface
     */
    public ConnectionInterface $connection;

    /**
     * The database query grammar instance.
     *
     * @var Grammar
     */
    public ?Grammar $grammar;

    /**
     * The database query post processor instance.
     *
     * @var Processor
     */
    public ?Processor $processor;

    /**
     * The current query value bindings.
     *
     * @var array
     */
    public array $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'groupBy' => [],
        'having' => [],
        'order' => [],
        'union' => [],
        'unionOrder' => [],
    ];

    /**
     * An aggregate function and column to be run.
     *
     * @var array
     */
    public array $aggregate;

    /**
     * The columns that should be returned.
     *
     * @var array
     */
    public array $columns;

    /**
     * Indicates if the query returns distinct results.
     *
     * Occasionally contains the columns that should be distinct.
     *
     * @var bool|array
     */
    public $distinct = false;

    /**
     * The table which the query is targeting.
     *
     * @var string
     */
    public ?string $from = null;

    /**
     * The table joins for the query.
     *
     * @var array
     */
    public ?array $joins = [];

    /**
     * The where constraints for the query.
     *
     * @var array
     */
    public array $wheres = [];

    /**
     * The groupings for the query.
     *
     * @var array
     */
    public ?array $groups = [];

    /**
     * The having constraints for the query.
     *
     * @var array
     */
    public ?array $havings = [];

    /**
     * The orderings for the query.
     *
     * @var array
     */
    public ?array $orders = [];

    /**
     * The maximum number of records to return.
     *
     * @var int
     */
    public ?int $limit = null;

    /**
     * The number of records to skip.
     *
     * @var int
     */
    public ?int $offset = null;

    /**
     * The query union statements.
     *
     * @var array
     */
    public ?array $unions = [];

    /**
     * The maximum number of union records to return.
     *
     * @var int
     */
    public ?int $unionLimit = null;

    /**
     * The number of union records to skip.
     *
     * @var int
     */
    public ?int $unionOffset = null;

    /**
     * The orderings for the union query.
     *
     * @var array
     */
    public ?array $unionOrders = [];

    /**
     * Indicates whether row locking is being used.
     *
     * @var string|bool
     */
    public $lock;

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    public array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * Whether use write pdo for select.
     *
     * @var bool
     */
    public bool $useWritePdo = false;

    /**
     * Create a new query builder instance.
     *
     * @param ConnectionInterface $connection
     * @param Grammar|null $grammar
     * @param Processor|null $processor
     * @return void
     */
    public function __construct(ConnectionInterface $connection,
                                Grammar $grammar = null,
                                Processor $processor = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?: $connection->getQueryGrammar();
        $this->processor = $processor ?: $connection->getPostProcessor();
    }

    /**
     * Set the columns to be selected.
     *
     * @param array|mixed $columns
     * @return $this
     */
    public function select($columns = ['*']): self
    {
        $this->columns = [];
        $this->bindings['select'] = [];
        $columns = is_array($columns) ? $columns : func_get_args();

        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {
                $this->selectSub($column, $as);
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    /**
     * Add a subselect expression to the query.
     *
     * @param $query
     * @param string $as
     * @return $this
     *
     */
    public function selectSub($query, string $as): self
    {
        [$query, $bindings] = $this->createSub($query);

        return $this->selectRaw(
            '(' . $query . ') as ' . $this->grammar->wrap($as), $bindings
        );
    }

    /**
     * Add a new "raw" select expression to the query.
     *
     * @param string $expression
     * @param array $bindings
     * @return $this
     */
    public function selectRaw(string $expression, array $bindings = []): self
    {
        $this->addSelect(new Expression($expression));

        if ($bindings) {
            $this->addBinding($bindings, 'select');
        }

        return $this;
    }

    /**
     * Makes "from" fetch from a subquery.
     *
     * @param Closure|Builder|string $query
     * @param string $as
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function fromSub($query, string $as): self
    {
        [$query, $bindings] = $this->createSub($query);

        return $this->fromRaw('(' . $query . ') as ' . $this->grammar->wrapTable($as), $bindings);
    }

    /**
     * Add a raw from clause to the query.
     *
     * @param string $expression
     * @param mixed $bindings
     * @return $this
     */
    public function fromRaw(string $expression, $bindings = []): self
    {
        $this->from = (string)(new Expression($expression));

        $this->addBinding($bindings, 'from');

        return $this;
    }

    /**
     * Creates a subquery and parse it.
     *
     * @param Closure|Builder|string $query
     * @return array
     */
    protected function createSub($query): array
    {
        // If the given query is a Closure, we will execute it while passing in a new
        // query instance to the Closure. This will give the developer a chance to
        // format and work with the query before we cast it to a raw SQL string.
        if ($query instanceof Closure) {
            $callback = $query;

            $callback($query = $this->forSubQuery());
        }

        return $this->parseSub($query);
    }

    /**
     * Parse the subquery into SQL and bindings.
     *
     * @param mixed $query
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function parseSub($query): ?array
    {
        if ($query instanceof self || $query instanceof EloquentBuilder || $query instanceof Relation) {
            return [$query->toSql(), $query->getBindings()];
        }

        if (is_string($query)) {
            return [$query, []];
        }

        throw new InvalidArgumentException(
            'A subquery must be a query builder instance, a Closure, or a string.'
        );
    }

    /**
     * Add a new select column to the query.
     *
     * @param array|mixed $column
     * @return $this
     */
    public function addSelect($column): self
    {
        $columns = is_array($column) ? $column : func_get_args();

        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {
                if (is_null($this->columns)) {
                    $this->select($this->from . '.*');
                }

                $this->selectSub($column, $as);
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * @return $this
     */
    public function distinct(): self
    {
        $columns = func_get_args();

        if (count($columns) > 0) {
            $this->distinct = is_array($columns[0]) || is_bool($columns[0]) ? $columns[0] : $columns;
        } else {
            $this->distinct = true;
        }

        return $this;
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param Closure|Builder|string $table
     * @param string|null $as
     * @return $this
     */
    public function from($table, ?string $as = null): self
    {
        if ($this->isQueryable($table)) {
            return $this->fromSub($table, $as);
        }

        $this->from = $as ? "{$table} as {$as}" : $table;

        return $this;
    }

    /**
     * Add a join clause to the query.
     *
     * @param string $table
     * @param Closure|string $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $type
     * @param bool $where
     * @return $this
     */
    public function join(string $table, $first, ?string $operator = null, ?string $second = null, string $type = 'inner', bool $where = false): self
    {
        $join = $this->newJoinClause($this, $type, $table);

        // If the first "column" of the join is really a Closure instance the developer
        // is trying to build a join with a complex "on" clause containing more than
        // one condition, so we'll add the join and call a Closure with the query.
        if ($first instanceof Closure) {
            $first($join);

            $this->joins[] = $join;

            $this->addBinding($join->getBindings(), 'join');
        }

        // If the column is simply a string, we can assume the join simply has a basic
        // "on" clause with a single condition. So we will just build the join with
        // this simple join clauses attached to it. There is not a join callback.
        else {
            $method = $where ? 'where' : 'on';

            $this->joins[] = $join->$method($first, $operator, $second);

            $this->addBinding($join->getBindings(), 'join');
        }

        return $this;
    }

    /**
     * Add a "join where" clause to the query.
     *
     * @param string $table
     * @param Closure|string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return $this
     */
    public function joinWhere(string $table, $first, string $operator, string $second, string $type = 'inner'): self
    {
        return $this->join($table, $first, $operator, $second, $type, true);
    }

    /**
     * Add a subquery join clause to the query.
     *
     * @param Closure|Builder|string $query
     * @param string $as
     * @param Closure|string $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $type
     * @param bool $where
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function joinSub($query, string $as, $first, ?string $operator = null, ?string $second = null, string $type = 'inner', bool $where = false): self
    {
        [$query, $bindings] = $this->createSub($query);

        $expression = '(' . $query . ') as ' . $this->grammar->wrapTable($as);

        $this->addBinding($bindings, 'join');

        return $this->join((string)(new Expression($expression)), $first, $operator, $second, $type, $where);
    }

    /**
     * Add a left join to the query.
     *
     * @param string $table
     * @param Closure|string $first
     * @param string|null $operator
     * @param string|null $second
     * @return $this
     */
    public function leftJoin(string $table, $first, ?string $operator = null, ?string $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a "join where" clause to the query.
     *
     * @param string $table
     * @param Closure|string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function leftJoinWhere(string $table, $first, string $operator, string $second): self
    {
        return $this->joinWhere($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a subquery left join to the query.
     *
     * @param Closure|Builder|string $query
     * @param string $as
     * @param Closure|string $first
     * @param string|null $operator
     * @param string|null $second
     * @return $this
     */
    public function leftJoinSub($query, string $as, $first, ?string $operator = null, ?string $second = null): self
    {
        return $this->joinSub($query, $as, $first, $operator, $second, 'left');
    }

    /**
     * Add a right join to the query.
     *
     * @param string $table
     * @param Closure|string $first
     * @param string|null $operator
     * @param string|null $second
     * @return $this
     */
    public function rightJoin(string $table, $first, ?string $operator = null, ?string $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a "right join where" clause to the query.
     *
     * @param string $table
     * @param Closure|string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function rightJoinWhere(string $table, $first, string $operator, string $second): self
    {
        return $this->joinWhere($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a subquery right join to the query.
     *
     * @param Closure|Builder|string $query
     * @param string $as
     * @param Closure|string $first
     * @param string|null $operator
     * @param string|null $second
     * @return $this
     */
    public function rightJoinSub($query, string $as, $first, ?string $operator = null, ?string $second = null): self
    {
        return $this->joinSub($query, $as, $first, $operator, $second, 'right');
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * @param string $table
     * @param Closure|string|null $first
     * @param string|null $operator
     * @param string|null $second
     * @return $this
     */
    public function crossJoin(string $table, $first = null, ?string $operator = null, ?string $second = null): self
    {
        if ($first) {
            return $this->join($table, $first, $operator, $second, 'cross');
        }

        $this->joins[] = $this->newJoinClause($this, 'cross', $table);

        return $this;
    }

    /**
     * Get a new join clause.
     *
     * @param Builder $parentQuery
     * @param string $type
     * @param string $table
     * @return JoinClause
     */
    protected function newJoinClause(self $parentQuery, string $type, string $table): JoinClause
    {
        return new JoinClause($parentQuery, $type, $table);
    }

    /**
     * Merge an array of where clauses and bindings.
     *
     * @param array $wheres
     * @param array $bindings
     * @return void
     */
    public function mergeWheres(array $wheres, array $bindings): void
    {
        $this->wheres = array_merge($this->wheres, (array)$wheres);

        $this->bindings['where'] = array_values(
            array_merge($this->bindings['where'], (array)$bindings)
        );
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param Closure|string|array $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, string $boolean = 'and'): self
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        if ($column instanceof Closure && is_null($operator)) {
            return $this->whereNested($column, $boolean);
        }

        // If the column is a Closure instance and there is an operator value, we will
        // assume the developer wants to run a subquery and then compare the result
        // of that subquery with the given value that was provided to the method.
        if ($this->isQueryable($column) && !is_null($operator)) {
            [$sub, $bindings] = $this->createSub($column);

            return $this->addBinding($bindings, 'where')
                ->where(new Expression('(' . $sub . ')'), $operator, $value, $boolean);
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        // If the value is a Closure, it means the developer is performing an entire
        // sub-select within the query and we will need to compile the sub-select
        // within the where clause to get the appropriate query record results.
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }

        // If the value is "null", we will just assume the developer wants to add a
        // where null clause to the query. So, we will allow a short-cut here to
        // that method for convenience so the developer doesn't have to check.
        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator !== '=');
        }

        $type = 'Basic';

        // If the column is making a JSON reference we'll check to see if the value
        // is a boolean. If it is, we'll add the raw boolean string as an actual
        // value to the query to ensure this is properly handled by the query.
        if (Str::contains($column, '->') && is_bool($value)) {
            $value = new Expression($value ? 'true' : 'false');

            if (is_string($column)) {
                $type = 'JsonBoolean';
            }
        }

        // Now that we are working with just a simple query we can put the elements
        // in our array and add the query binding to our array of bindings that
        // will be bound to each SQL statements when it is finally executed.
        $this->wheres[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );

        if (!$value instanceof Expression) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    /**
     * Add an array of where clauses to the query.
     *
     * @param array $column
     * @param string $boolean
     * @param string $method
     * @return $this
     */
    protected function addArrayOfWheres(array $column, string $boolean, string $method = 'where'): self
    {
        return $this->whereNested(static function ($query) use ($column, $method, $boolean) {
            foreach ($column as $key => $value) {
                if (is_numeric($key) && is_array($value)) {
                    $query->{$method}(...array_values($value));
                } else {
                    $query->$method($key, '=', $value, $boolean);
                }
            }
        }, $boolean);
    }

    /**
     * Prepare the value and operator for a where clause.
     *
     * @param string $value
     * @param string $operator
     * @param bool $useDefault
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function prepareValueAndOperator(?string $value, ?string $operator, bool $useDefault = false): array
    {
        if ($useDefault) {
            return [$operator, '='];
        }

        if ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$value, $operator];
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * Prevents using Null values with invalid operators.
     *
     * @param string $operator
     * @param mixed $value
     * @return bool
     */
    protected function invalidOperatorAndValue(?string $operator, $value): bool
    {
        return is_null($value) && in_array($operator, $this->operators, true) &&
            !in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Determine if the given operator is supported.
     *
     * @param string $operator
     * @return bool
     */
    protected function invalidOperator(string $operator): bool
    {
        return !in_array(strtolower($operator), $this->operators, true) &&
            !in_array(strtolower($operator), $this->grammar->getOperators(), true);
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param Closure|string|array $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add a "where" clause comparing two columns to the query.
     *
     * @param string|array $first
     * @param string|null $operator
     * @param string|null $second
     * @param string|null $boolean
     * @return $this
     */
    public function whereColumn($first, ?string $operator = null, ?string $second = null, ?string $boolean = 'and'): self
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($first)) {
            return $this->addArrayOfWheres($first, $boolean, 'whereColumn');
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if ($this->invalidOperator($operator)) {
            [$second, $operator] = [$operator, '='];
        }

        // Finally, we will add this where clause into this array of clauses that we
        // are building for the query. All of them will be compiled via a grammar
        // once the query is about to be executed and run against the database.
        $type = 'Column';

        $this->wheres[] = compact(
            'type', 'first', 'operator', 'second', 'boolean'
        );

        return $this;
    }

    /**
     * Add an "or where" clause comparing two columns to the query.
     *
     * @param string|array $first
     * @param string|null $operator
     * @param string|null $second
     * @return $this
     */
    public function orWhereColumn($first, ?string $operator = null, ?string $second = null): self
    {
        return $this->whereColumn($first, $operator, $second, 'or');
    }

    /**
     * Add a raw where clause to the query.
     *
     * @param string $sql
     * @param mixed $bindings
     * @param string $boolean
     * @return $this
     */
    public function whereRaw(string $sql, $bindings = [], string $boolean = 'and'): self
    {
        $this->wheres[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => $boolean];

        $this->addBinding((array)$bindings, 'where');

        return $this;
    }

    /**
     * Add a raw or where clause to the query.
     *
     * @param string $sql
     * @param mixed $bindings
     * @return $this
     */
    public function orWhereRaw(string $sql, $bindings = []): self
    {
        return $this->whereRaw($sql, $bindings, 'or');
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn(string $column, $values, string $boolean = 'and', bool $not = false): self
    {
        $type = $not ? 'NotIn' : 'In';

        // If the value is a query builder instance we will assume the developer wants to
        // look for any values that exists within this given query. So we will add the
        // query accordingly so that this query is properly executed when it is run.
        if ($this->isQueryable($values)) {
            [$query, $bindings] = $this->createSub($values);

            $values = [new Expression($query)];

            $this->addBinding($bindings, 'where');
        }

        // Next, if the value is Arrayable we need to cast it to its raw array form so we
        // have the underlying array value instead of an Arrayable object which is not
        // able to be added as a binding, etc. We will then add to the wheres array.
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        // Finally we'll add a binding for each values unless that value is an expression
        // in which case we will just skip over it since it will be the query as a raw
        // string and not as a parameterized place-holder to be replaced by the PDO.
        $this->addBinding($this->cleanBindings($values), 'where');

        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @return $this
     */
    public function orWhereIn(string $column, $values): self
    {
        return $this->whereIn($column, $values, 'or');
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn(string $column, $values, string $boolean = 'and'): self
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @return $this
     */
    public function orWhereNotIn(string $column, $values): self
    {
        return $this->whereNotIn($column, $values, 'or');
    }

    /**
     * Add a "where in raw" clause for integer values to the query.
     *
     * @param string $column
     * @param Arrayable|array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIntegerInRaw(string $column, $values, string $boolean = 'and', bool $not = false): self
    {
        $type = $not ? 'NotInRaw' : 'InRaw';

        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        foreach ($values as &$value) {
            $value = (int)$value;
        }

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        return $this;
    }

    /**
     * Add an "or where in raw" clause for integer values to the query.
     *
     * @param string $column
     * @param Arrayable|array $values
     * @return $this
     */
    public function orWhereIntegerInRaw(string $column, $values): self
    {
        return $this->whereIntegerInRaw($column, $values, 'or');
    }

    /**
     * Add a "where not in raw" clause for integer values to the query.
     *
     * @param string $column
     * @param Arrayable|array $values
     * @param string $boolean
     * @return $this
     */
    public function whereIntegerNotInRaw(string $column, $values, string $boolean = 'and'): self
    {
        return $this->whereIntegerInRaw($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not in raw" clause for integer values to the query.
     *
     * @param string $column
     * @param Arrayable|array $values
     * @return $this
     */
    public function orWhereIntegerNotInRaw(string $column, $values): self
    {
        return $this->whereIntegerNotInRaw($column, $values, 'or');
    }

    /**
     * Add a "where null" clause to the query.
     *
     * @param string|array $columns
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereNull($columns, string $boolean = 'and', bool $not = false): self
    {
        $type = $not ? 'NotNull' : 'Null';

        foreach (Arr::wrap($columns) as $column) {
            $this->wheres[] = compact('type', 'column', 'boolean');
        }

        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
     *
     * @param string $column
     * @return $this
     */
    public function orWhereNull(string $column): self
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * Add a "where not null" clause to the query.
     *
     * @param string|array $columns
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull($columns, string $boolean = 'and'): self
    {
        return $this->whereNull($columns, $boolean, true);
    }

    /**
     * Add a where between statement to the query.
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): self
    {
        $type = 'between';

        $this->wheres[] = compact('type', 'column', 'values', 'boolean', 'not');

        $this->addBinding($this->cleanBindings($values), 'where');

        return $this;
    }

    /**
     * Add an or where between statement to the query.
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function orWhereBetween(string $column, array $values): self
    {
        return $this->whereBetween($column, $values, 'or');
    }

    /**
     * Add a where not between statement to the query.
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotBetween(string $column, array $values, string $boolean = 'and'): self
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add an or where not between statement to the query.
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function orWhereNotBetween(string $column, array $values): self
    {
        return $this->whereNotBetween($column, $values, 'or');
    }

    /**
     * Add an "or where not null" clause to the query.
     *
     * @param string $column
     * @return $this
     */
    public function orWhereNotNull(string $column): self
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Add a "where date" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|null $value
     * @param string $boolean
     * @return $this
     */
    public function whereDate(string $column, string $operator, $value = null, string $boolean = 'and'): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        return $this->addDateBasedWhere('Date', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where date" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|null $value
     * @return $this
     */
    public function orWhereDate(string $column, string $operator, $value = null): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereDate($column, $operator, $value, 'or');
    }

    /**
     * Add a "where time" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|null $value
     * @param string $boolean
     * @return $this
     */
    public function whereTime(string $column, string $operator, $value = null, string $boolean = 'and'): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('H:i:s');
        }

        return $this->addDateBasedWhere('Time', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where time" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|null $value
     * @return $this
     */
    public function orWhereTime(string $column, string $operator, $value = null): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereTime($column, $operator, $value, 'or');
    }

    /**
     * Add a "where day" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|null $value
     * @param string $boolean
     * @return $this
     */
    public function whereDay(string $column, string $operator, $value = null, string $boolean = 'and'): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('d');
        }

        if (!$value instanceof Expression) {
            $value = str_pad($value, 2, '0', STR_PAD_LEFT);
        }

        return $this->addDateBasedWhere('Day', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where day" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|null $value
     * @return $this
     */
    public function orWhereDay(string $column, string $operator, $value = null): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereDay($column, $operator, $value, 'or');
    }

    /**
     * Add a "where month" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|null $value
     * @param string $boolean
     * @return $this
     */
    public function whereMonth(string $column, string $operator, $value = null, string $boolean = 'and'): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('m');
        }

        if (!$value instanceof Expression) {
            $value = str_pad($value, 2, '0', STR_PAD_LEFT);
        }

        return $this->addDateBasedWhere('Month', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where month" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|null $value
     * @return $this
     */
    public function orWhereMonth(string $column, string $operator, $value = null): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereMonth($column, $operator, $value, 'or');
    }

    /**
     * Add a "where year" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|int|null $value
     * @param string $boolean
     * @return $this
     */
    public function whereYear(string $column, string $operator, $value = null, string $boolean = 'and'): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y');
        }

        return $this->addDateBasedWhere('Year', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where year" statement to the query.
     *
     * @param string $column
     * @param string $operator
     * @param DateTimeInterface|string|int|null $value
     * @return $this
     */
    public function orWhereYear(string $column, string $operator, $value = null): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereYear($column, $operator, $value, 'or');
    }

    /**
     * Add a date based (year, month, day, time) statement to the query.
     *
     * @param string $type
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    protected function addDateBasedWhere(string $type, string $column, string $operator, $value, string $boolean = 'and'): self
    {
        $this->wheres[] = compact('column', 'type', 'boolean', 'operator', 'value');

        if (!$value instanceof Expression) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param Closure $callback
     * @param string $boolean
     * @return $this
     */
    public function whereNested(Closure $callback, string $boolean = 'and'): self
    {
        $callback($query = $this->forNestedWhere());

        return $this->addNestedWhereQuery($query, $boolean);
    }

    /**
     * Create a new query instance for nested where condition.
     *
     * @return Builder
     */
    public function forNestedWhere(): Builder
    {
        return $this->newQuery()->from($this->from);
    }

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param $query
     * @param string $boolean
     * @return $this
     */
    public function addNestedWhereQuery(Builder $query, string $boolean = 'and'): self
    {
        if (count($query->wheres)) {
            $type = 'Nested';

            $this->wheres[] = compact('type', 'query', 'boolean');

            $this->addBinding($query->getRawBindings()['where'], 'where');
        }

        return $this;
    }

    /**
     * Add a full sub-select to the query.
     *
     * @param string $column
     * @param string $operator
     * @param Closure $callback
     * @param string $boolean
     * @return $this
     */
    protected function whereSub(string $column, string $operator, Closure $callback, string $boolean): self
    {
        $type = 'Sub';

        // Once we have the query instance we can simply execute it so it can add all
        // of the sub-select's conditions to itself, and then we can cache it off
        // in the array of where clauses for the "main" parent query instance.
        $callback($query = $this->forSubQuery());

        $this->wheres[] = compact(
            'type', 'column', 'operator', 'query', 'boolean'
        );

        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Add an exists clause to the query.
     *
     * @param Closure $callback
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereExists(Closure $callback, string $boolean = 'and', bool $not = false): self
    {
        $query = $this->forSubQuery();

        // Similar to the sub-select clause, we will create a new query instance so
        // the developer may cleanly specify the entire exists query and we will
        // compile the whole thing in the grammar and insert it into the SQL.
        $callback($query);

        return $this->addWhereExistsQuery($query, $boolean, $not);
    }

    /**
     * Add an or exists clause to the query.
     *
     * @param Closure $callback
     * @param bool $not
     * @return $this
     */
    public function orWhereExists(Closure $callback, bool $not = false): self
    {
        return $this->whereExists($callback, 'or', $not);
    }

    /**
     * Add a where not exists clause to the query.
     *
     * @param Closure $callback
     * @param string $boolean
     * @return $this
     */
    public function whereNotExists(Closure $callback, string $boolean = 'and'): self
    {
        return $this->whereExists($callback, $boolean, true);
    }

    /**
     * Add a where not exists clause to the query.
     *
     * @param Closure $callback
     * @return $this
     */
    public function orWhereNotExists(Closure $callback): self
    {
        return $this->orWhereExists($callback, true);
    }

    /**
     * Add an exists clause to the query.
     *
     * @param Builder $query
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function addWhereExistsQuery(self $query, string $boolean = 'and', bool $not = false): self
    {
        $type = $not ? 'NotExists' : 'Exists';

        $this->wheres[] = compact('type', 'query', 'boolean');

        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Adds a where condition using row values.
     *
     * @param array $columns
     * @param string $operator
     * @param array $values
     * @param string $boolean
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function whereRowValues(array $columns, string $operator, array $values, string $boolean = 'and'): self
    {
        if (count($columns) !== count($values)) {
            throw new InvalidArgumentException('The number of columns must match the number of values');
        }

        $type = 'RowValues';

        $this->wheres[] = compact('type', 'columns', 'operator', 'values', 'boolean');

        $this->addBinding($this->cleanBindings($values));

        return $this;
    }

    /**
     * Adds a or where condition using row values.
     *
     * @param array $columns
     * @param string $operator
     * @param array $values
     * @return $this
     */
    public function orWhereRowValues(array $columns, string $operator, array $values): self
    {
        return $this->whereRowValues($columns, $operator, $values, 'or');
    }

    /**
     * Add a "where JSON contains" clause to the query.
     *
     * @param string $column
     * @param mixed $value
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereJsonContains(string $column, $value, string $boolean = 'and', bool $not = false): self
    {
        $type = 'JsonContains';

        $this->wheres[] = compact('type', 'column', 'value', 'boolean', 'not');

        if (!$value instanceof Expression) {
            $this->addBinding($this->grammar->prepareBindingForJsonContains($value));
        }

        return $this;
    }

    /**
     * Add a "or where JSON contains" clause to the query.
     *
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function orWhereJsonContains(string $column, $value): self
    {
        return $this->whereJsonContains($column, $value, 'or');
    }

    /**
     * Add a "where JSON not contains" clause to the query.
     *
     * @param string $column
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function whereJsonDoesntContain(string $column, $value, string $boolean = 'and'): self
    {
        return $this->whereJsonContains($column, $value, $boolean, true);
    }

    /**
     * Add a "or where JSON not contains" clause to the query.
     *
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function orWhereJsonDoesntContain(string $column, $value): self
    {
        return $this->whereJsonDoesntContain($column, $value, 'or');
    }

    /**
     * Add a "where JSON length" clause to the query.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function whereJsonLength(string $column, $operator, $value = null, string $boolean = 'and'): self
    {
        $type = 'JsonLength';

        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');

        if (!$value instanceof Expression) {
            $this->addBinding($value);
        }

        return $this;
    }

    /**
     * Add a "or where JSON length" clause to the query.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhereJsonLength(string $column, $operator, $value = null): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereJsonLength($column, $operator, $value, 'or');
    }

    /**
     * Handles dynamic "where" clauses to the query.
     *
     * @param string $method
     * @param array $parameters
     * @return $this
     */
    public function dynamicWhere(string $method, array $parameters): self
    {
        $finder = substr($method, 5);

        $segments = preg_split(
            '/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE
        );

        // The connector variable will determine which connector will be used for the
        // query condition. We will change it as we come across new boolean values
        // in the dynamic method strings, which could contain a number of these.
        $connector = 'and';

        $index = 0;

        foreach ($segments as $segment) {
            // If the segment is not a boolean connector, we can assume it is a column's name
            // and we will add it to the query as a new constraint as a where clause, then
            // we can keep iterating through the dynamic method string's segments again.
            if ($segment !== 'And' && $segment !== 'Or') {
                $this->addDynamic($segment, $connector, $parameters, $index);

                $index++;
            }

            // Otherwise, we will store the connector so we know how the next where clause we
            // find in the query should be connected to the previous ones, meaning we will
            // have the proper boolean connector to connect the next where clause found.
            else {
                $connector = $segment;
            }
        }

        return $this;
    }

    /**
     * Add a single dynamic where clause statement to the query.
     *
     * @param string $segment
     * @param string $connector
     * @param array $parameters
     * @param int $index
     * @return void
     */
    protected function addDynamic(string $segment, string $connector, array $parameters, int $index): void
    {
        // Once we have parsed out the columns and formatted the boolean operators we
        // are ready to add it to this query as a where clause just like any other
        // clause on the query. Then we'll increment the parameter index values.
        $bool = strtolower($connector);

        $this->where(Str::snake($segment), '=', $parameters[$index], $bool);
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param array|string ...$groups
     * @return $this
     */
    public function groupBy(...$groups): self
    {
        foreach ($groups as $group) {
            $this->groups = array_merge(
                (array)$this->groups,
                Arr::wrap($group)
            );
        }

        return $this;
    }

    /**
     * Add a raw groupBy clause to the query.
     *
     * @param string $sql
     * @param array $bindings
     * @return $this
     */
    public function groupByRaw(string $sql, array $bindings = []): self
    {
        $this->groups[] = new Expression($sql);

        $this->addBinding($bindings, 'groupBy');

        return $this;
    }

    /**
     * Add a "having" clause to the query.
     *
     * @param string $column
     * @param string|null $operator
     * @param string|null $value
     * @param string $boolean
     * @return $this
     */
    public function having(string $column, ?string $operator = null, ?string $value = null, string $boolean = 'and'): self
    {
        $type = 'Basic';

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        $this->havings[] = compact('type', 'column', 'operator', 'value', 'boolean');

        if (!$value instanceof Expression) {
            $this->addBinding($value, 'having');
        }

        return $this;
    }

    /**
     * Add a "or having" clause to the query.
     *
     * @param string $column
     * @param string|null $operator
     * @param string|null $value
     * @return $this
     */
    public function orHaving(string $column, ?string $operator = null, ?string $value = null): self
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * Add a "having between " clause to the query.
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function havingBetween(string $column, array $values, string $boolean = 'and', bool $not = false): self
    {
        $type = 'between';

        $this->havings[] = compact('type', 'column', 'values', 'boolean', 'not');

        $this->addBinding($this->cleanBindings($values), 'having');

        return $this;
    }

    /**
     * Add a raw having clause to the query.
     *
     * @param string $sql
     * @param array $bindings
     * @param string $boolean
     * @return $this
     */
    public function havingRaw(string $sql, array $bindings = [], string $boolean = 'and'): self
    {
        $type = 'Raw';

        $this->havings[] = compact('type', 'sql', 'boolean');

        $this->addBinding($bindings, 'having');

        return $this;
    }

    /**
     * Add a raw or having clause to the query.
     *
     * @param string $sql
     * @param array $bindings
     * @return $this
     */
    public function orHavingRaw(string $sql, array $bindings = []): self
    {
        return $this->havingRaw($sql, $bindings, 'or');
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param Closure|Builder|Expression|string $column
     * @param string $direction
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function orderBy($column, string $direction = 'asc'): self
    {
        if ($this->isQueryable($column)) {
            [$query, $bindings] = $this->createSub($column);

            $column = new Expression('(' . $query . ')');

            $this->addBinding($bindings, $this->unions ? 'unionOrder' : 'order');
        }

        $direction = strtolower($direction);

        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Order direction must be "asc" or "desc".');
        }

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Add a descending "order by" clause to the query.
     *
     * @param string $column
     * @return $this
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param string $column
     * @return $this
     */
    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param string $column
     * @return $this
     */
    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'asc');
    }

    /**
     * Put the query's results in random order.
     *
     * @param string $seed
     * @return $this
     */
    public function inRandomOrder(string $seed = ''): self
    {
        return $this->orderByRaw($this->grammar->compileRandom($seed));
    }

    /**
     * Add a raw "order by" clause to the query.
     *
     * @param string $sql
     * @param array $bindings
     * @return $this
     */
    public function orderByRaw(string $sql, array $bindings = []): self
    {
        $type = 'Raw';

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = compact('type', 'sql');

        $this->addBinding($bindings, $this->unions ? 'unionOrder' : 'order');

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function skip(int $value): self
    {
        return $this->offset($value);
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function offset(int $value): self
    {
        $property = $this->unions ? 'unionOffset' : 'offset';

        $this->$property = max(0, $value);

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function take(int $value): self
    {
        return $this->limit($value);
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function limit(int $value): self
    {
        $property = $this->unions ? 'unionLimit' : 'limit';

        if ($value >= 0) {
            $this->$property = $value;
        }

        return $this;
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param int $page
     * @param int $perPage
     * @return $this
     */
    public function forPage(int $page, int $perPage = 15): self
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    /**
     * Constrain the query to the previous "page" of results before a given ID.
     *
     * @param int $perPage
     * @param int|null $lastId
     * @param string $column
     * @return $this
     */
    public function forPageBeforeId(int $perPage = 15, ?int $lastId = 0, string $column = 'id'): self
    {
        $this->orders = $this->removeExistingOrdersFor($column);

        if (!is_null($lastId)) {
            $this->where($column, '<', $lastId);
        }

        return $this->orderBy($column, 'desc')
            ->limit($perPage);
    }

    /**
     * Constrain the query to the next "page" of results after a given ID.
     *
     * @param int $perPage
     * @param int|null $lastId
     * @param string $column
     * @return $this
     */
    public function forPageAfterId(int $perPage = 15, ?int $lastId = 0, string $column = 'id'): self
    {
        $this->orders = $this->removeExistingOrdersFor($column);

        if (!is_null($lastId)) {
            $this->where($column, '>', $lastId);
        }

        return $this->orderBy($column, 'asc')
            ->limit($perPage);
    }

    /**
     * Remove all existing orders and optionally add a new order.
     *
     * @param string|null $column
     * @param string $direction
     * @return $this
     */
    public function reorder(?string $column = null, string $direction = 'asc'): self
    {
        $this->orders = null;
        $this->unionOrders = null;
        $this->bindings['order'] = [];
        $this->bindings['unionOrder'] = [];

        if ($column) {
            return $this->orderBy($column, $direction);
        }

        return $this;
    }

    /**
     * Get an array with all orders with a given column removed.
     *
     * @param string $column
     * @return array
     */
    protected function removeExistingOrdersFor(string $column): array
    {
        return Collection::make($this->orders)
            ->reject(static function ($order) use ($column) {
                return isset($order['column'])
                    ? $order['column'] === $column : false;
            })->values()->all();
    }

    /**
     * Add a union statement to the query.
     *
     * @param Builder|Closure $query
     * @param bool $all
     * @return $this
     */
    public function union($query, bool $all = false): self
    {
        if ($query instanceof Closure) {
            $query($query = $this->newQuery());
        }

        $this->unions[] = compact('query', 'all');

        $this->addBinding($query->getBindings(), 'union');

        return $this;
    }

    /**
     * Add a union all statement to the query.
     *
     * @param Builder|Closure $query
     * @return $this
     */
    public function unionAll($query): self
    {
        return $this->union($query, true);
    }

    /**
     * Lock the selected rows in the table.
     *
     * @param string|bool $value
     * @return $this
     */
    public function lock($value = true): self
    {
        $this->lock = $value;

        if (!is_null($this->lock)) {
            $this->useWritePdo();
        }

        return $this;
    }

    /**
     * Lock the selected rows in the table for updating.
     *
     * @return Builder
     */
    public function lockForUpdate(): Builder
    {
        return $this->lock(true);
    }

    /**
     * Share lock the selected rows in the table.
     *
     * @return Builder
     */
    public function sharedLock(): Builder
    {
        return $this->lock(false);
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toSql(): string
    {
        return $this->grammar->compileSelect($this);
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param int|string $id
     * @param array $columns
     * @return mixed|static
     */
    public function find($id, array $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param string $column
     * @return mixed
     */
    public function value(string $column)
    {
        $result = (array)$this->first([$column]);

        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array|string $columns
     * @return Collection
     */
    public function get($columns = ['*']): Collection
    {
        return collect($this->onceWithColumns(Arr::wrap($columns), function () {
            return $this->processor->processSelect($this, $this->runSelect());
        }));
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
    protected function runSelect(): array
    {
        return $this->connection->select(
            $this->toSql(), $this->getBindings(), !$this->useWritePdo
        );
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return LengthAwarePaginator
     * @throws BindingResolutionException
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $total = $this->getCountForPagination();

        $results = $total ? $this->forPage($page, $perPage)->get($columns) : collect();

        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get a paginator only supporting simple next and previous links.
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return \Mini\Contracts\Pagination\Paginator
     * @throws BindingResolutionException
     */
    public function simplePaginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): \Mini\Contracts\Pagination\Paginator
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $this->offset(($page - 1) * $perPage)->limit($perPage + 1);

        return $this->simplePaginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get the count of the total records for the paginator.
     *
     * @param array $columns
     * @return int
     */
    public function getCountForPagination(array $columns = ['*']): int
    {
        $results = $this->runPaginationCountQuery($columns);

        // Once we have run the pagination count query, we will get the resulting count and
        // take into account what type of query it was. When there is a group by we will
        // just return the count of the entire results set since that will be correct.
        if (!isset($results[0])) {
            return 0;
        }

        if (is_object($results[0])) {
            return (int)$results[0]->aggregate;
        }

        return (int)array_change_key_case((array)$results[0])['aggregate'];
    }

    /**
     * Run a pagination count query.
     *
     * @param array $columns
     * @return array
     */
    protected function runPaginationCountQuery(array $columns = ['*']): array
    {
        if ($this->groups || $this->havings) {
            $clone = $this->cloneForPaginationCount();

            if (!empty($this->joins) && is_null($clone->columns)) {
                $clone->select($this->from . '.*');
            }

            return $this->newQuery()
                ->from(new Expression('(' . $clone->toSql() . ') as ' . $this->grammar->wrap('aggregate_table')))
                ->mergeBindings($clone)
                ->setAggregate('count', $this->withoutSelectAliases($columns))
                ->get()->all();
        }

        $without = $this->unions ? ['orders', 'limit', 'offset'] : ['columns', 'orders', 'limit', 'offset'];

        return $this->cloneWithout($without)
            ->cloneWithoutBindings($this->unions ? ['order'] : ['select', 'order'])
            ->setAggregate('count', $this->withoutSelectAliases($columns))
            ->get()->all();
    }

    /**
     * Clone the existing query instance for usage in a pagination subquery.
     *
     * @return self
     */
    protected function cloneForPaginationCount(): self
    {
        return $this->cloneWithout(['orders', 'limit', 'offset'])
            ->cloneWithoutBindings(['order']);
    }

    /**
     * Remove the column aliases since they will break count queries.
     *
     * @param array $columns
     * @return array
     */
    protected function withoutSelectAliases(array $columns): array
    {
        return array_map(static function ($column) {
            return is_string($column) && ($aliasPosition = stripos($column, ' as ')) !== false
                ? substr($column, 0, $aliasPosition) : $column;
        }, $columns);
    }

    /**
     * Get a lazy collection for the given query.
     *
     * @return LazyCollection
     */
    public function cursor(): LazyCollection
    {
        if (is_null($this->columns)) {
            $this->columns = ['*'];
        }

        return new LazyCollection(function () {
            yield from $this->connection->cursor(
                $this->toSql(), $this->getBindings(), !$this->useWritePdo
            );
        });
    }

    /**
     * Throw an exception if the query doesn't have an orderBy clause.
     *
     * @return void
     *
     * @throws RuntimeException
     */
    protected function enforceOrderBy(): void
    {
        if (empty($this->orders) && empty($this->unionOrders)) {
            throw new RuntimeException('You must specify an orderBy clause when using this function.');
        }
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column
     * @param string|null $key
     * @return Collection
     */
    public function pluck(string $column, ?string $key = null): Collection
    {
        // First, we will need to select the results of the query accounting for the
        // given columns / key. Once we have the results, we will be able to take
        // the results and get the exact data that was requested for the query.
        $queryResult = $this->onceWithColumns(
            is_null($key) ? [$column] : [$column, $key],
            function () {
                return $this->processor->processSelect(
                    $this, $this->runSelect()
                );
            }
        );

        if (empty($queryResult)) {
            return collect();
        }

        // If the columns are qualified with a table or have an alias, we cannot use
        // those directly in the "pluck" operations since the results from the DB
        // are only keyed by the column itself. We'll strip the table out here.
        $column = $this->stripTableForPluck($column);

        $key = $this->stripTableForPluck($key);

        return is_array($queryResult[0])
            ? $this->pluckFromArrayColumn($queryResult, $column, $key)
            : $this->pluckFromObjectColumn($queryResult, $column, $key);
    }

    /**
     * Strip off the table name or alias from a column identifier.
     *
     * @param string $column
     * @return string|null
     */
    protected function stripTableForPluck(?string $column): ?string
    {
        if (is_null($column)) {
            return $column;
        }

        $separator = stripos($column, ' as ') !== false ? ' as ' : '\.';

        return last(preg_split('~' . $separator . '~i', $column));
    }

    /**
     * Retrieve column values from rows represented as objects.
     *
     * @param array $queryResult
     * @param string $column
     * @param string|null $key
     * @return Collection
     */
    protected function pluckFromObjectColumn(array $queryResult, string $column, ?string $key): Collection
    {
        $results = [];

        if (is_null($key)) {
            foreach ($queryResult as $row) {
                $results[] = $row->$column;
            }
        } else {
            foreach ($queryResult as $row) {
                $results[$row->$key] = $row->$column;
            }
        }

        return collect($results);
    }

    /**
     * Retrieve column values from rows represented as arrays.
     *
     * @param array $queryResult
     * @param string $column
     * @param string|null $key
     * @return Collection
     */
    protected function pluckFromArrayColumn(array $queryResult, string $column, ?string $key): Collection
    {
        $results = [];

        if (is_null($key)) {
            foreach ($queryResult as $row) {
                $results[] = $row[$column];
            }
        } else {
            foreach ($queryResult as $row) {
                $results[$row[$key]] = $row[$column];
            }
        }

        return collect($results);
    }

    /**
     * Concatenate values of a given column as a string.
     *
     * @param string $column
     * @param string $glue
     * @return string
     */
    public function implode(string $column, string $glue = ''): string
    {
        return $this->pluck($column)->implode($glue);
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists(): bool
    {
        $results = $this->connection->select(
            $this->grammar->compileExists($this), $this->getBindings(), !$this->useWritePdo
        );

        // If the results has rows, we will get the row and see if the exists column is a
        // boolean true. If there is no results for this query we will return false as
        // there are no rows for this query at all and we can return that info here.
        if (isset($results[0])) {
            $results = (array)$results[0];

            return (bool)$results['exists'];
        }

        return false;
    }

    /**
     * Determine if no rows exist for the current query.
     *
     * @return bool
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * Execute the given callback if no rows exist for the current query.
     *
     * @param Closure $callback
     * @return mixed
     */
    public function existsOr(Closure $callback)
    {
        return $this->exists() ? true : $callback();
    }

    /**
     * Execute the given callback if rows exist for the current query.
     *
     * @param Closure $callback
     * @return mixed
     */
    public function doesntExistOr(Closure $callback)
    {
        return $this->doesntExist() ? true : $callback();
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param string $columns
     * @return int
     */
    public function count(string $columns = '*'): int
    {
        return (int)$this->aggregate(__FUNCTION__, Arr::wrap($columns));
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param string $column
     * @return mixed
     */
    public function min(string $column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param string $column
     * @return mixed
     */
    public function max(string $column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param string $column
     * @return mixed
     */
    public function sum(string $column)
    {
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ?: 0;
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param string $column
     * @return mixed
     */
    public function avg(string $column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Alias for the "avg" method.
     *
     * @param string $column
     * @return mixed
     */
    public function average(string $column)
    {
        return $this->avg($column);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param string $function
     * @param array $columns
     * @return mixed
     */
    public function aggregate(string $function, array $columns = ['*'])
    {
        $results = $this->cloneWithout($this->unions ? [] : ['columns'])
            ->cloneWithoutBindings($this->unions ? [] : ['select'])
            ->setAggregate($function, $columns)
            ->get($columns);

        if (!$results->isEmpty()) {
            return array_change_key_case((array)$results[0])['aggregate'];
        }
    }

    /**
     * Execute a numeric aggregate function on the database.
     *
     * @param string $function
     * @param array $columns
     * @return float|int
     */
    public function numericAggregate(string $function, array $columns = ['*'])
    {
        $result = $this->aggregate($function, $columns);

        // If there is no result, we can obviously just return 0 here. Next, we will check
        // if the result is an integer or float. If it is already one of these two data
        // types we can just return the result as-is, otherwise we will convert this.
        if (!$result) {
            return 0;
        }

        if (is_int($result) || is_float($result)) {
            return $result;
        }

        // If the result doesn't contain a decimal place, we will assume it is an int then
        // cast it to one. When it does we will cast it to a float since it needs to be
        // cast to the expected data type for the developers out of pure convenience.
        return strpos((string)$result, '.') === false
            ? (int)$result : (float)$result;
    }

    /**
     * Set the aggregate property without running the query.
     *
     * @param string $function
     * @param array $columns
     * @return $this
     */
    protected function setAggregate(string $function, array $columns): self
    {
        $this->aggregate = compact('function', 'columns');

        if (empty($this->groups)) {
            $this->orders = null;

            $this->bindings['order'] = [];
        }

        return $this;
    }

    /**
     * Execute the given callback while selecting the given columns.
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param array $columns
     * @param callable $callback
     * @return mixed
     */
    protected function onceWithColumns(array $columns, callable $callback)
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        $result = $callback();

        $this->columns = $original;

        return $result;
    }

    /**
     * Insert a new record into the database.
     *
     * @param array $values
     * @return bool
     */
    public function insert(array $values): bool
    {
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
        if (empty($values)) {
            return true;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        // Here, we will sort the insert keys for every record so that each insert is
        // in the same order for the record. We need to make sure this is the case
        // so there are not any errors or problems when inserting these records.
        else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.
        return $this->connection->insert(
            $this->grammar->compileInsert($this, $values),
            $this->cleanBindings(Arr::flatten($values, 1))
        );
    }

    /**
     * Insert a new record into the database while ignoring errors.
     *
     * @param array $values
     * @return int
     */
    public function insertOrIgnore(array $values): int
    {
        if (empty($values)) {
            return 0;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        return $this->connection->affectingStatement(
            $this->grammar->compileInsertOrIgnore($this, $values),
            $this->cleanBindings(Arr::flatten($values, 1))
        );
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param array $values
     * @param string|null $sequence
     * @return int
     */
    public function insertGetId(array $values, ?string $sequence = null): int
    {
        $sql = $this->grammar->compileInsertGetId($this, $values, $sequence);

        $values = $this->cleanBindings($values);

        return $this->processor->processInsertGetId($this, $sql, $values, $sequence);
    }

    /**
     * Insert new records into the table using a subquery.
     *
     * @param array $columns
     * @param Closure|Builder|string $query
     * @return int
     */
    public function insertUsing(array $columns, $query): int
    {
        [$sql, $bindings] = $this->createSub($query);

        return $this->connection->affectingStatement(
            $this->grammar->compileInsertUsing($this, $columns, $sql),
            $this->cleanBindings($bindings)
        );
    }

    /**
     * Update a record in the database.
     *
     * @param array $values
     * @return int
     */
    public function update(array $values): int
    {
        $sql = $this->grammar->compileUpdate($this, $values);

        return $this->connection->update($sql, $this->cleanBindings(
            $this->grammar->prepareBindingsForUpdate($this->bindings, $values)
        ));
    }

    /**
     * Insert or update a record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     * @return bool
     */
    public function updateOrInsert(array $attributes, array $values = []): bool
    {
        if (!$this->where($attributes)->exists()) {
            return $this->insert(array_merge($attributes, $values));
        }

        if (empty($values)) {
            return true;
        }

        return (bool)$this->limit(1)->update($values);
    }

    /**
     * Increment a column's value by a given amount.
     *
     * @param string $column
     * @param float|int $amount
     * @param array $extra
     * @return int
     *
     * @throws InvalidArgumentException
     */
    public function increment(string $column, $amount = 1, array $extra = []): int
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Non-numeric value passed to increment method.');
        }

        $wrapped = $this->grammar->wrap($column);

        $columns = array_merge([$column => $this->raw("$wrapped + $amount")], $extra);

        return $this->update($columns);
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param string $column
     * @param float|int $amount
     * @param array $extra
     * @return int
     *
     * @throws InvalidArgumentException
     */
    public function decrement(string $column, $amount = 1, array $extra = []): int
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Non-numeric value passed to decrement method.');
        }

        $wrapped = $this->grammar->wrap($column);

        $columns = array_merge([$column => $this->raw("$wrapped - $amount")], $extra);

        return $this->update($columns);
    }

    /**
     * Delete a record from the database.
     *
     * @param mixed $id
     * @return int
     */
    public function delete($id = null): int
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
        if (!is_null($id)) {
            $this->where($this->from . '.id', '=', $id);
        }

        return $this->connection->delete(
            $this->grammar->compileDelete($this), $this->cleanBindings(
            $this->grammar->prepareBindingsForDelete($this->bindings)
        )
        );
    }

    /**
     * Run a truncate statement on the table.
     *
     * @return void
     */
    public function truncate(): void
    {
        foreach ($this->grammar->compileTruncate($this) as $sql => $bindings) {
            $this->connection->statement($sql, $bindings);
        }
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return Builder
     */
    public function newQuery(): self
    {
        return new static($this->connection, $this->grammar, $this->processor);
    }

    /**
     * Create a new query instance for a sub-query.
     *
     * @return Builder
     */
    protected function forSubQuery(): self
    {
        return $this->newQuery();
    }

    /**
     * Create a raw database expression.
     *
     * @param mixed $value
     * @return Expression
     */
    public function raw($value): Expression
    {
        return $this->connection->raw($value);
    }

    /**
     * Get the current query value bindings in a flattened array.
     *
     * @return array
     */
    public function getBindings(): array
    {
        return Arr::flatten($this->bindings);
    }

    /**
     * Get the raw array of bindings.
     *
     * @return array
     */
    public function getRawBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Set the bindings on the query builder.
     *
     * @param array $bindings
     * @param string $type
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setBindings(array $bindings, string $type = 'where'): self
    {
        if (!array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        $this->bindings[$type] = $bindings;

        return $this;
    }

    /**
     * Add a binding to the query.
     *
     * @param mixed $value
     * @param string $type
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function addBinding($value, string $type = 'where'): self
    {
        if (!array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
        } else {
            $this->bindings[$type][] = $value;
        }

        return $this;
    }

    /**
     * Merge an array of bindings into our bindings.
     *
     * @param Builder $query
     * @return $this
     */
    public function mergeBindings(Builder $query): self
    {
        $this->bindings = array_merge_recursive($this->bindings, $query->bindings);

        return $this;
    }

    /**
     * Remove all of the expressions from a list of bindings.
     *
     * @param array $bindings
     * @return array
     */
    protected function cleanBindings(array $bindings): array
    {
        return array_values(array_filter($bindings, static function ($binding) {
            return !$binding instanceof Expression;
        }));
    }

    /**
     * Get the default key name of the table.
     *
     * @return string
     */
    protected function defaultKeyName(): string
    {
        return 'id';
    }

    /**
     * Get the database connection instance.
     *
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Get the database query processor instance.
     *
     * @return Processor
     */
    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    /**
     * Get the query grammar instance.
     *
     * @return Grammar
     */
    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    /**
     * Use the write pdo for query.
     *
     * @return $this
     */
    public function useWritePdo(): self
    {
        $this->useWritePdo = true;

        return $this;
    }

    /**
     * Determine if the value is a query builder instance or a Closure.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isQueryable($value): bool
    {
        return $value instanceof self ||
            $value instanceof EloquentBuilder ||
            $value instanceof Relation ||
            $value instanceof Closure;
    }

    /**
     * Clone the query without the given properties.
     *
     * @param array $properties
     * @return static
     */
    public function cloneWithout(array $properties)
    {
        return tap(clone $this, static function ($clone) use ($properties) {
            foreach ($properties as $property) {
                $clone->{$property} = null;
            }
        });
    }

    /**
     * Clone the query without the given bindings.
     *
     * @param array $except
     * @return static
     */
    public function cloneWithoutBindings(array $except)
    {
        return tap(clone $this, static function ($clone) use ($except) {
            foreach ($except as $type) {
                $clone->bindings[$type] = [];
            }
        });
    }

    /**
     * Dump the current SQL and bindings.
     *
     * @return $this
     */
    public function dump(): self
    {
        dump($this->toSql(), $this->getBindings());

        return $this;
    }

    /**
     * Die and dump the current SQL and bindings.
     *
     * @return void
     */
    public function dd(): void
    {
        dd($this->toSql(), $this->getBindings());
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (Str::startsWith($method, 'where')) {
            return $this->dynamicWhere($method, $parameters);
        }

        static::throwBadMethodCallException($method);
    }
}
