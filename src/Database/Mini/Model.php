<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use Throwable;

class Model
{

    protected DB $db;

    protected array $variables;
    protected string $primaryKey;
    protected string $table;

    public function __construct($data = [])
    {
        $this->db = new DB();
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
        if (is_array($this->variables) && array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }
        return null;
    }

    /**
     * @param array $data
     * @return mixed|null
     */
    public function update($data = [])
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
    public function save($data = [])
    {
        $pk = $this->variables[$this->primaryKey] ?? null;
        return is_null($pk) ? $this->insert($data) : $this->update($data);
    }

    /**
     * @param array $data
     * @return int|mixed
     */
    public function insert($data = [])
    {
        $this->variables = $data ?: $this->variables;

        if (!empty($this->variables)) {
            $fields = array_keys($this->variables);
            $fields_val = array(implode(',', array_map(function ($field) {
                return '`' . $field . '`';
            }, $fields)), ':' . implode(',:', $fields));
            $sql = 'INSERT INTO ' . $this->table . ' (' . $fields_val[0] . ') VALUES (' . $fields_val[1] . ')';
        } else {
            $sql = 'INSERT INTO ' . $this->table . ' () VALUES ()';
        }
        if ($this->exec($sql)) {
            return $this->db->lastInsertId();
        }
        return 0;
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function delete($id = '')
    {
        $id = (empty($this->variables[$this->primaryKey])) ? $id : $this->variables[$this->primaryKey];

        if (!empty($id)) {
            $sql = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryKey . '= :' . $this->primaryKey . ' LIMIT 1';
            return $this->exec($sql, array($this->primaryKey => $id));
        }
    }

    /**
     * @param string $id
     * @return array|mixed
     */
    public function find($id = '')
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
     * @param mixed $fields
     * @param int $limit
     * @return array|null
     */
    public function column($fields = '', $limit = 0): ?array
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
     * @param array $fields .
     * @param array $sort .
     * @return array of Collection.
     */
    public function search($fields = [], $sort = [])
    {
        $this->variables = $fields ?: $this->variables;

        $sql = 'SELECT * FROM ' . $this->table;

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
    public function all()
    {
        return $this->db->query('SELECT * FROM ' . $this->table);
    }

    /**
     * @param $field
     * @return mixed
     */
    public function min($field)
    {
        if ($field) {
            return $this->db->single('SELECT min(' . $field . ')' . ' FROM ' . $this->table);
        }
    }

    /**
     * @param $field
     * @return mixed
     */
    public function max($field)
    {
        if ($field) {
            return $this->db->single('SELECT max(' . $field . ')' . ' FROM ' . $this->table);
        }
    }

    /**
     * @param $field
     * @return mixed
     */
    public function avg($field)
    {
        if ($field) {
            return $this->db->single('SELECT avg(' . $field . ')' . ' FROM ' . $this->table);
        }
    }

    /**
     * @param $field
     * @return mixed
     */
    public function sum($field)
    {
        if ($field) {
            return $this->db->single('SELECT sum(' . $field . ')' . ' FROM ' . $this->table);
        }
    }

    /**
     * @param $field
     * @return mixed
     */
    public function count($field)
    {
        if ($field) {
            return $this->db->single('SELECT count(' . $field . ')' . ' FROM ' . $this->table);
        }
    }


    /**
     * @param $sql
     * @param null $array
     * @return mixed|null
     */
    private function exec($sql, $array = null)
    {

        $array = $array ?: $this->variables;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
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
    public function transaction(callable $callable, $args = [])
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