<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use PDO;
use PDOException;
use Swoole\Coroutine;
use Throwable;

class DB
{
    protected object $connection;

    private ?object $prepare = null;

    private array $parameters = [];

    protected Pool $pool;

    protected string $key = '';

    public function __construct(array $config = [], string $key = '')
    {
        $this->pool = Pool::getInstance($config);
        $this->key = $key;
        $this->connection();
    }

    /**
     * @param string $connection_key
     */
    public function connection(string $connection_key = ''): void
    {
        $this->connection = $this->pool->getConnection($connection_key ?: $this->key);
    }

    /**
     * @param $query
     * @param array $parameters
     */
    private function initialize($query, array $parameters = []): void
    {
        try {
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
        } catch (PDOException $exception) {
            throw $exception;
        }
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
        if (empty($this->parameters) && is_array($paras)) {
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
    public function query($query, array $params = [], $mode = PDO::FETCH_ASSOC)
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
    public function lastInsertId()
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
    public function column($query, $params = []): ?array
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
    public function row($query, array $params = [], $mod = PDO::FETCH_ASSOC)
    {
        $this->initialize($query, $params);
        return $this->prepare->fetch($mod);
    }

    /**
     * @param $query
     * @param array $params
     * @return mixed
     */
    public function single($query, array $params = [])
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
    public function transaction(callable $callable, $args = [])
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
