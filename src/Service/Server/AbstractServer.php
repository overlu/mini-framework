<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Exceptions\Handler;
use Mini\Exceptions\InvalidResponseException;
use Mini\Listener;
use Mini\Provider\BaseProviderService;
use Mini\Provider\BaseRequestService;
use Mini\Service\Watch\Runner;
use Mini\Support\Command;
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

    protected string $type = 'Custom';

    protected int $worker_num = 1;

    /**
     * AbstractServer constructor.
     * @throws Throwable
     * @throws InvalidResponseException
     */
    public function __construct()
    {
        try {
            $this->initialize();
            $this->server->on('workerStart', [$this, 'onWorkerStart']);
            $this->server->set($this->config['settings']);

            if ($this->config['mode'] === SWOOLE_BASE) {
                $this->server->on('managerStart', [$this, 'onManagerStart']);
            } else {
                $this->server->on('start', [$this, 'onStart']);
            }
            foreach ($this->config['callbacks'] as $eventKey => $callbackItem) {
                [$class, $func] = $callbackItem;
                $this->server->on($eventKey, [$class, $func]);
            }
            $this->server->start();
        } catch (Throwable $throwable) {
            (new Handler($throwable))->throw();
        }
    }

    abstract public function initialize(): void;

    /**
     * @param Server $server
     * @throws Throwable
     */
    public function onStart(Server $server): void
    {
        Command::infoWithTime("🚀 Mini {$this->type} Server [{$this->worker_num} workers] running：http://{$this->config['ip']}:{$this->config['port']}...");
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
        Listener::getInstance()->listen('workerStart', $server, $workerId);
        try {
            BaseProviderService::getInstance()->register($server, $workerId);
            BaseProviderService::getInstance()->boot($server, $workerId);
        } catch (Throwable $throwable) {
            Command::error($throwable);
        }
    }

    /**
     * @param Server $server
     * @throws Throwable
     */
    public function onManagerStart(Server $server): void
    {
        Command::infoWithTime("🚀 Mini {$this->type} Server running：http://{$this->config['ip']}:{$this->config['port']}...");
        Listener::getInstance()->listen('managerStart', $server);
    }

    public function onRequest(Request $request, Response $response): void
    {
        try {
            BaseRequestService::getInstance()->before($request, $response);
        } catch (Throwable $throwable) {
            Command::error($throwable);
        }
    }

    public function onReceive(Server $server, $fd, $fromId, $data): void
    {
//        Listener::getInstance()->listen('receive', $server);
    }

    public function onTask(Server $server): void
    {
//        Listener::getInstance()->listen('task', $server);
    }

    public function onFinish(Server $server): void
    {
//        Listener::getInstance()->listen('finish', $server);
    }

}