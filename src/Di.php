<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

class Di
{
    use Singleton;

    private array $container = [];

    private array $aliases = [];


    /**
     * @param $key
     * @param $obj
     * @param mixed ...$arg
     */
    public function bind(string $key, $obj, ...$arg): void
    {
        $this->container[$key] = array(
            "obj" => $obj,
            "params" => $arg,
        );
    }

    public function alias(string $key, string $alias): void
    {
        $this->aliases[$alias] = $key;
    }

    /**
     * @param $key
     */
    public function delete(string $key): void
    {
        unset($this->container[$key]);
    }


    public function clear(): void
    {
        $this->container = array();
    }

    /**
     * @param string $key
     * @param array $params
     * @return mixed|null
     */
    public function make(string $key, array $params = [])
    {
        $key = $this->aliases[$key] ?? $key;
        if (isset($this->container[$key])) {
            $obj = $this->container[$key]['obj'];
            $params = $params ?: $this->container[$key]['params'];
            if (is_callable($obj)) {
                return call($obj, $params);
            }
            if (is_object($obj)) {
                return $obj;
            }
            if (is_string($obj) && class_exists($obj)) {
                $this->container[$key]['obj'] = new $obj(...$params);
                return $this->container[$key]['obj'];
            }
            return $obj;
        }
        return null;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->container;
    }
}