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
use Mini\Service\Watch\Runner;
use Mini\Support\Command;
use Mini\Support\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
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

    private $events = [
        'shutdown', 'workerStart', 'workerStop', 'workerExit', 'connect', 'receive', 'packet', 'close', 'task', 'finish', 'pipeMessage', 'workerError', 'managerStop', 'beforeReload', 'afterReload', 'request', 'handShake', 'open', 'message'
    ];

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
                throw new \Exception('server key: [' . $this->key . '] not exists in config/servers.php');
            }
            $this->worker_num = $this->config['settings']['worker_num'] ?? 1;
            $this->initialize();
            $this->server->set($this->config['settings']);
            $this->eventDispatch();
            \Mini\Server::getInstance()->set(self::class, $this->server);
            $this->server->start();
        } catch (Throwable $throwable) {
            (new Handler($throwable))->throw();
        }
    }

    private function eventDispatch()
    {
        foreach ($this->events as $event) {
            $method = 'on' . ucfirst($event);
            if (method_exists($this, $method)) {
                $this->server->on($event, [$this, $method]);
            }
        }
        foreach ($this->config['callbacks'] as $event => $callbackItem) {
            if (!method_exists($this, 'on' . ucfirst($event))) {
                $this->server->on($event, $callbackItem);
            }
        }
        if ($this->config['mode'] === SWOOLE_BASE) {
            $this->server->on('managerStart', [$this, 'onManagerStart']);
        } else {
            $this->server->on('start', [$this, 'onStart']);
        }
    }

    abstract public function initialize(): void;

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
        $type = ucfirst($this->type);
        Command::infoWithTime("🚀 Mini {$type} Server [{$this->worker_num} workers] running：{$this->config['ip']}:{$this->config['port']}...\"");
        Listener::getInstance()->listen('managerStart', $server);
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
//
    public function onTask(Server $server): void
    {
        Listener::getInstance()->listen('task', $server);
    }

    public function onFinish(Server $server): void
    {
        Listener::getInstance()->listen('finish', $server);
    }
}