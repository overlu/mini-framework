<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

class Server
{
    use Singleton;

    private array $container = [];


    /**
     * @param $key
     * @param $server
     */
    public function set(string $key, \Swoole\Server $server): void
    {
        $this->container[$key] = $server;
    }

    public function reload(): void
    {
        foreach ($this->container as $server) {
            $server->reload();
        }
    }

    public function stop(): void
    {
        foreach ($this->container as $server) {
            $server->shutdown();
        }
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
        foreach ($this->container as $server) {
            $server->shutdown();
        }
        $this->container = array();
    }

    /**
     * @param $key
     * @return array|mixed
     */
    public function get(string $key)
    {
        return $this->container[$key] ?? [];
    }

    public function all(): array
    {
        return $this->container;
    }
}