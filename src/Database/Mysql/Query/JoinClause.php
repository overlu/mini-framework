<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query;

use Closure;
use Mini\Database\Mysql\ConnectionInterface;
use Mini\Database\Mysql\Query\Grammars\Grammar;
use Mini\Database\Mysql\Query\Processors\Processor;

class JoinClause extends Builder
{
    /**
     * The type of join being performed.
     *
     * @var string
     */
    public string $type;

    /**
     * The table the join clause is joining to.
     *
     * @var string
     */
    public string $table;

    /**
     * The connection of the parent query builder.
     *
     * @var ConnectionInterface
     */
    protected ConnectionInterface $parentConnection;

    /**
     * The grammar of the parent query builder.
     *
     * @var Grammar
     */
    protected Grammar $parentGrammar;

    /**
     * The processor of the parent query builder.
     *
     * @var Processor
     */
    protected Processor $parentProcessor;

    /**
     * The class name of the parent query builder.
     *
     * @var string
     */
    protected string $parentClass;

    /**
     * Create a new join clause instance.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $parentQuery
=======
     * @param Builder $parentQuery
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $type
     * @param string $table
     * @return void
     */
    public function __construct(Builder $parentQuery, string $type, string $table)
    {
        $this->type = $type;
        $this->table = $table;
        $this->parentClass = get_class($parentQuery);
        $this->parentGrammar = $parentQuery->getGrammar();
        $this->parentProcessor = $parentQuery->getProcessor();
        $this->parentConnection = $parentQuery->getConnection();

        parent::__construct(
            $this->parentConnection, $this->parentGrammar, $this->parentProcessor
        );
    }

    /**
     * Add an "on" clause to the join.
     *
     * On clauses can be chained, e.g.
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * on `contacts`.`user_id` = `users`.`id` and `contacts`.`info_id` = `info`.`id`
     *
<<<<<<< HEAD
     * @param \Closure|string $first
=======
     * @param Closure|string $first
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string|null $operator
     * @param string|null $second
     * @param string $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function on($first, ?string $operator = null, ?string $second = null, string $boolean = 'and'): self
    {
        if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        }

        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
     *
<<<<<<< HEAD
     * @param \Closure|string $first
     * @param string|null $operator
     * @param string|null $second
     * @return \Mini\Database\Mysql\Query\JoinClause
=======
     * @param Closure|string $first
     * @param string|null $operator
     * @param string|null $second
     * @return JoinClause
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function orOn($first, ?string $operator = null, ?string $second = null): self
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Get a new instance of the join clause builder.
     *
     * @return JoinClause
     */
    public function newQuery(): self
    {
        return new static($this->newParentQuery(), $this->type, $this->table);
    }

    /**
     * Create a new query instance for sub-query.
     *
     * @return Builder
     */
    protected function forSubQuery(): Builder
    {
        return $this->newParentQuery()->newQuery();
    }

    /**
     * Create a new parent query instance.
     *
     * @return Builder
     */
    protected function newParentQuery(): Builder
    {
        $class = $this->parentClass;

        return new $class($this->parentConnection, $this->parentGrammar, $this->parentProcessor);
    }
}
