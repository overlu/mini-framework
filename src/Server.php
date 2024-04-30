<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Swoole\Table;

class Server
{
    use Singleton;

    private \Swoole\Server $server;

    private ?Table $table = null;

    /**
     * @param \Swoole\Server $server
     */
    public function set(\Swoole\Server $server): void
    {
        $this->server = $server;
    }

    public function setTable(Table $table): void
    {
        $this->table = $table;
    }

    /**
     * @return Table|null
     */
    public function getTable(): ?Table
    {
        return $this->table;
    }

    /**
     * @param bool $only_reload_task_worker
     */
    public function reload(bool $only_reload_task_worker = false): void
    {
        $this->server->reload($only_reload_task_worker);
    }

    public function stop(): void
    {
        $this->server->shutdown();
    }


    public function clear(): void
    {
        $this->server->shutdown();
    }

    /**
     * @return \Swoole\Server
     */
    public function get(): \Swoole\Server
    {
        return $this->server;
    }
}