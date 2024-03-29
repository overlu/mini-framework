<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use Mini\Contracts\MiniDB;
use PDO;
use Swoole\Coroutine;
use Swoole\Database\PDOProxy;
use Throwable;

class DB implements MiniDB
{
    protected PDOProxy $connection;

    private ?object $prepare = null;

    private array $parameters = [];

    protected Pool $pool;

    protected string $key = '';

    public function __construct(string $key = '')
    {
        $this->pool = app('db.mini.pool');
        $this->key = $key;
        $this->connection();
    }

    /**
     * @param string $connection_key
     * @return DB
     */
    public function connection(string $connection_key = ''): DB
    {
        $this->connection = $this->pool->getConnection($connection_key ?: $this->key);
        return $this;
    }

    /**
     * @param string $query
     * @param array $parameters
     */
    private function initialize(string $query, array $parameters = []): void
    {
        $this->prepare = $this->connection->prepare($query);
        $this->bindMore($parameters);

        if (!empty($this->parameters)) {
            foreach ($this->parameters as $param => $value) {
                if (is_int($value[1])) {
                    $type = PDO::PARAM_INT;
                } else if (is_bool($value[1])) {
                    $type = PDO::PARAM_BOOL;
                } else if (is_null($value[1])) {
                    $type = PDO::PARAM_NULL;
                } else {
                    $type = PDO::PARAM_STR;
                }
                $this->prepare->bindValue($value[0], $value[1], $type);
            }
        }
        $this->prepare->execute();
        $this->parameters = [];
    }

    public function bind($para, $value): void
    {
        $this->parameters[] = [":" . $para, $value];
    }

    /**
     * @param array $paras
     */
    public function bindMore(array $paras): void
    {
        if (empty($this->parameters)) {
            foreach ($paras as $key => $value) {
                $this->bind($key, $value);
            }
        }
    }

    /**
     * @param $query
     * @param array $params
     * @param int $mode
     * @return null|mixed
     */
    public function query($query, array $params = [], int $mode = PDO::FETCH_ASSOC): mixed
    {
        $query = trim(str_replace("\r", " ", $query));
        $this->initialize($query, $params);

        $rawStatement = explode(" ", preg_replace("/\s+|\t+|\n+/", " ", $query));
        $statement = strtolower($rawStatement[0]);
        $res = null;
        if (in_array($statement, ['select', 'show'])) {
            $res = $this->prepare->fetchAll($mode);
        }
        if (in_array($statement, ['insert', 'update', 'delete'])) {
            $res = $this->prepare->rowCount();
        }
        return $res;
    }

    /**
     * @return mixed
     */
    public function lastInsertId(): mixed
    {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * @param $query
     * @param array $params
     * @return array|null
     */
    public function column($query, array $params = []): ?array
    {
        $this->initialize($query, $params);
        $columns = $this->prepare->fetchAll(PDO::FETCH_NUM);

        $column = null;

        foreach ($columns as $cells) {
            $column[] = $cells[0];
        }
        return $column;

    }

    /**
     * @param $query
     * @param array $params
     * @param int $mod
     * @return mixed
     */
    public function row($query, array $params = [], int $mod = PDO::FETCH_ASSOC): mixed
    {
        $this->initialize($query, $params);
        return $this->prepare->fetch($mod);
    }

    /**
     * @param $query
     * @param array $params
     * @return mixed
     */
    public function single($query, array $params = []): mixed
    {
        $this->initialize($query, $params);
        return $this->prepare->fetchColumn();
    }

    protected function isPoolConnection(): bool
    {
        return Coroutine::getCid() > 0;
    }

    /**
     * @param callable $callable
     * @param array $args
     * @return mixed|null
     * @throws Throwable
     */
    public function transaction(callable $callable, array $args = []): mixed
    {
        try {
            $this->beginTransaction();
            $result = call($callable, $args);
            $this->commit();
            return $result;
        } catch (Throwable $throwable) {
            $this->rollBack();
            throw $throwable;
        }
    }
}
