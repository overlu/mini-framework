<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Context;
use Mini\Exceptions\Handler;
use Mini\Exceptions\InvalidResponseException;
use Mini\Listener;
use Mini\Provider\BaseProviderService;
use Mini\Provider\BaseRequestService;
use Mini\RemoteShell;
use Mini\Service\Watch\Runner;
use Mini\Support\Command;
use Mini\Support\Coroutine;
use RuntimeException;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\Table;
use Throwable;

abstract class AbstractServer
{
    /**
     * @var Server
     */
    protected Server $server;

    protected array $config;

    protected string $type = '';

    protected int $worker_num = 1;

    private array $events = [
        'shutdown',
        'workerStart',
        'workerStop',
        'workerExit',
        'connect',
        'receive',
        'packet',
        'close',
        'task',
        'finish',
        'pipeMessage',
        'workerError',
        'managerStop',
        'beforeReload',
        'afterReload',
        'request',
        'handShake',
        'open',
        'message'
    ];
    protected string $key;

    /**
     * AbstractServer constructor.
     * @param string $key
     * @throws InvalidResponseException
     * @throws Throwable
     */
    public function __construct($key = '')
    {
        try {
            $this->key = $key;
            $this->config = config('servers.' . $this->key, []);
            if (empty($this->config)) {
                throw new RuntimeException('server key: [' . $this->key . '] not exists in config/servers.php');
            }
            $this->worker_num = $this->config['settings']['worker_num'] ?? swoole_cpu_num();
            $this->initialize();
            $this->setServerConfig();
            $this->eventDispatch();
            \Mini\Server::getInstance()->set($this->server);
            if (config('debugger.enable_remote_debug', false)) {
                RemoteShell::listen($this->server, config('debugger.listener.host', '127.0.0.1'), config('debugger.listener.port', 9559));
            }
            $this->server->start();
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }

    abstract public function initialize(): void;

    private function setServerConfig(): void
    {
        $this->server->set($this->config['settings']);
    }

    private function eventDispatch(): void
    {
        $this->callbackDispatch();
        $this->eventOnDispatch();
        $this->startDispatch();
        $this->swooleTableDispatch();
    }

    private function swooleTableDispatch(): void
    {
        $table = new Table(4096, 0.2);
        $table->column('value', Table::TYPE_STRING, 4096);
        $table->column('expire', Table::TYPE_INT, 4);
        $table->create();
        $this->server->table = $table;
    }

    private function eventOnDispatch(): void
    {
        foreach ($this->events as $event) {
            $method = 'on' . ucfirst($event);
            if (method_exists($this, $method)) {
                $this->server->on($event, [$this, $method]);
            } else {
                Listener::getInstance()->on($this->server, $event);
            }
        }
    }

    private function callbackDispatch(): void
    {
        foreach ($this->config['callbacks'] as $event => $callbackItem) {
            if (!method_exists($this, 'on' . ucfirst($event))) {
                $this->server->on($event, $callbackItem);
            }
        }
    }

    private function startDispatch(): void
    {
        if ($this->config['mode'] === SWOOLE_BASE) {
            $this->server->on('managerStart', [$this, 'onManagerStart']);
        } else {
            $this->server->on('start', [$this, 'onStart']);
        }
    }

    /**
     * @param Server $server
     * @throws Throwable
     */
    public function onStart(Server $server): void
    {
        $type = ucfirst($this->type ?: $this->key);
        Command::infoWithTime("🚀 Mini {$type} Server [{$this->worker_num} workers] running：{$this->config['ip']}:{$this->config['port']}...");
        Listener::getInstance()->listen('start', $server);
        if (config('mini.hot_reload') && config('mini.env', 'local') !== 'production') {
            Runner::start();
        }
    }

    /**
     * @param Server $server
     * @param int $workerId
     * @throws Throwable
     */
    public function onWorkerStart(Server $server, int $workerId): void
    {
        try {
            BaseProviderService::getInstance()->register($server, $workerId);
            BaseProviderService::getInstance()->boot($server, $workerId);
        } catch (Throwable $throwable) {
            Command::error($throwable);
        }
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }

    /**
     * @param Server $server
     * @throws Throwable
     */
    public function onManagerStart(Server $server): void
    {
        $type = ucfirst($this->type ?: $this->key);
        Command::infoWithTime("🚀 Mini {$type} Server [{$this->worker_num} workers] running：{$this->config['ip']}:{$this->config['port']}...\"");
        Listener::getInstance()->listen('managerStart', $server);
        if (config('mini.hot_reload') && config('mini.env', 'local') !== 'production') {
            Runner::start();
        }
    }

    public function onRequest(Request $request, Response $response): void
    {
        try {
            Context::set('IsInRequestEvent', true);
            Listener::getInstance()->listen('request', $request, $response);
        } catch (Throwable $throwable) {
            Command::error($throwable);
        }
    }

//    public function onReceive(Server $server, $fd, $fromId, $data): void
//    {
//        Listener::getInstance()->listen('receive', $server);
//    }

    public function onTask(Server $server, Server\Task $task)
    {
        try {
            $data = $task->data;
            if (isset($data['type'], $data['params'])) {
                $response = '';
                if ($data['type'] === 'events') {
                    $response = app('events')->dispatch(...(array)$data['params']);
                }
                if ($data['type'] === 'callable') {
                    $response = call_user_func_array(\Opis\Closure\unserialize($data['callable']), (array)$data['params']);
                }
                return $task->finish($response);
            }
            Listener::getInstance()->listen('task', $server);
        } catch (Throwable $throwable) {
            Command::error($throwable);
        }
    }

    public function onFinish(Server $server, int $task_id, $data): void
    {
        Listener::getInstance()->listen('finish', $server, $task_id, $data);
    }
}