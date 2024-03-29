<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use Throwable;

/**
 * Class Model
 * @package Mini\Database\Mini
 * @mixin DB
 */
class Model
{

    protected DB $db;

    protected array $variables = [];
    protected string $primaryKey = 'id';
    protected string $table = '';

    public function __construct($data = [])
    {
        $this->db = app('db.mini');
        $this->variables = $data;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (strtolower($name) === $this->primaryKey) {
            $this->variables[$this->primaryKey] = $value;
        } else {
            $this->variables[$name] = $value;
        }
    }

    public function __isset($name)
    {
        return isset($this->name);
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->db->$name(...$arguments);
    }

    /**
     * @param array $data
     * @return mixed|null
     */
    public function update(array $data = []): mixed
    {
        $this->variables = $data ?: $this->variables;
        $this->variables[$this->primaryKey] = (empty($this->variables[$this->primaryKey])) ? 'no_pk' : $this->variables[$this->primaryKey];

        $fields_val = '';
        $columns = array_keys($this->variables);

        foreach ($columns as $column) {
            if ($column !== $this->primaryKey) {
                $fields_val .= '`' . $column . '` = :' . $column . ',';
            }
        }

        $fields_val = substr_replace($fields_val, '', -1);

        if (count($columns) > 1) {

            $sql = 'UPDATE ' . $this->table . ' SET ' . $fields_val . ' WHERE ' . $this->primaryKey . '= :' . $this->primaryKey;
            if ($this->variables[$this->primaryKey] === 'no_pk') {
                unset($this->variables[$this->primaryKey]);
                $sql = 'UPDATE ' . $this->table . ' SET ' . $fields_val;
            }

            return $this->exec($sql);
        }

        return null;
    }

    /**
     * @param array $data
     * @return int|mixed|null
     */
    public function save(array $data = []): mixed
    {
        $pk = $this->variables[$this->primaryKey] ?? null;
        return is_null($pk) ? $this->insert($data) : $this->update($data);
    }

    /**
     * @param array $data
     * @return int|mixed
     */
    public function insert(array $data = []): mixed
    {
        $this->variables = $data ?: $this->variables;

        if (!empty($this->variables)) {
            $fields = array_keys($this->variables);
            $fields_val = array(implode(',', array_map(static function ($field) {
                return '`' . $field . '`';
            }, $fields)), ':' . implode(',:', $fields));
            $sql = 'INSERT INTO ' . $this->table . ' (' . $fields_val[0] . ') VALUES (' . $fields_val[1] . ')';
        } else {
            $sql = 'INSERT INTO ' . $this->table . ' () VALUES ()';
        }
        $id = $this->variables[$this->primaryKey] ?? null;
        if ($this->exec($sql)) {
            return $id ?: $this->db->lastInsertId();
        }
        return 0;
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function delete(string $id = ''): mixed
    {
        $id = (empty($this->variables[$this->primaryKey])) ? $id : $this->variables[$this->primaryKey];

        if (!empty($id)) {
            $sql = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryKey . '= :' . $this->primaryKey . ' LIMIT 1';
            return $this->exec($sql, array($this->primaryKey => $id));
        }
        return null;
    }

    /**
     * @param string $id
     * @return array|mixed
     */
    public function find(string $id = ''): mixed
    {
        $id = (empty($this->variables[$this->primaryKey])) ? $id : $this->variables[$this->primaryKey];
        $result = [];
        if (!empty($id)) {
            $sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $this->primaryKey . '= :' . $this->primaryKey . ' LIMIT 1';

            $result = $this->db->row($sql, array($this->primaryKey => $id));
            $this->variables = $result ?: [];
        }
        return $result;
    }

    /**
     * @param mixed|string $fields
     * @param int $limit
     * @return array|null
     */
    public function column(mixed $fields = '', int $limit = 0): ?array
    {
        $result = [];
        if ($fields) {
            $fields = is_array($fields) ? implode(',', array_map(static function ($field) {
                return '`' . $field . '`';
            }, $fields)) : $fields;
            $sql = 'SELECT ' . $fields . ' FROM ' . $this->table . ($limit ? ' Limit ' . (int)$limit : '');
            $result = $this->db->column($sql);
        }
        return $result;
    }

    /**
     * @param array $wheres
     * @param array|string $fields .
     * @param array $sort .
     * @return mixed|null
     */
    public function search(array $wheres = [], array|string $fields = ['*'], array $sort = []): mixed
    {
        $this->variables = $wheres ?: $this->variables;

        $sql = 'SELECT ' . (is_array($fields) ? implode(',', $fields) : $fields) . ' FROM ' . $this->table;

        if (!empty($this->variables)) {
            $fields_val = array();
            $columns = array_keys($this->variables);
            foreach ($columns as $column) {
                $fields_val [] = '`' . $column . '` = :' . $column;
            }
            $sql .= ' WHERE ' . implode(' AND ', $fields_val);
        }

        if (!empty($sort)) {
            $sort_values = [];
            foreach ($sort as $key => $value) {
                $sort_values[] = $key . ' ' . $value;
            }
            $sql .= ' ORDER BY ' . implode(', ', $sort_values);
        }
        return $this->exec($sql);
    }

    /**
     * @return mixed|null
     */
    public function all(): mixed
    {
        return $this->db->query('SELECT * FROM ' . $this->table);
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function min(string $field): mixed
    {
        return $this->db->single('SELECT min(' . $field . ')' . ' FROM ' . $this->table);
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function max(string $field): mixed
    {
        return $this->db->single('SELECT max(' . $field . ')' . ' FROM ' . $this->table);
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function avg(string $field): mixed
    {
        return $this->db->single('SELECT avg(' . $field . ')' . ' FROM ' . $this->table);
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function sum(string $field): mixed
    {
        return $this->db->single('SELECT sum(' . $field . ')' . ' FROM ' . $this->table);
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function count(string $field): mixed
    {
        return $this->db->single('SELECT count(' . $field . ')' . ' FROM ' . $this->table);
    }


    /**
     * @param $sql
     * @param array|null $array
     * @return mixed|null
     */
    private function exec($sql, array $array = null): mixed
    {

        $array = $array ?: $this->variables;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }
        $result = $this->db->query($sql, $array);
        $this->variables = [];
        return $result;
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
            $this->db->beginTransaction();
            $result = call($callable, $args);
            $this->db->commit();
            return $result;
        } catch (Throwable $throwable) {
            $this->db->rollBack();
            throw $throwable;
        }
    }
}
