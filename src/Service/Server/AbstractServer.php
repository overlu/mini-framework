<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Bootstrap;
use Mini\Context;
use Mini\Contracts\Foundation\Application;
use Mini\Crontab\Crontab;
use Mini\Exception\Handler;
use Mini\Facades\Redis;
use Mini\Listener;
use Mini\RemoteShell;
use Mini\Service\Watch\Runner;
use Mini\Support\Command;
use RuntimeException;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process;
use Swoole\Server;
use Swoole\Table;
use Swoole\WebSocket\Frame;
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
     * @param bool $daemonize
     * @throws Throwable
     */
    public function __construct(string $key = '', bool $daemonize = false)
    {
        try {
            $this->key = $key;
            $this->config = config('servers.' . $this->key, []);
            if (empty($this->config)) {
                throw new RuntimeException('server key: [' . $this->key . '] not exists in config/servers.php');
            }
            if ($daemonize) {
                $this->config['settings']['daemonize'] = 1;
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
            Handler::getInstance()->throw($throwable);
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
        $this->crontabDispatch();
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

    private function crontabDispatch(): void
    {
        if (config('crontab.enable_crontab', false)) {
            $process = new Process(function () {
                Bootstrap::getInstance()->initProviderService();
                app('providers')->bootstrap($this->server, getmypid());
                Crontab::run();
            });
            $this->server->addProcess($process);
        }
    }

    /**
     * @param Server $server
     * @throws Throwable
     */
    public function onStart(Server $server): void
    {
        $type = $this->type ?: $this->key;
        Command::infoWithTime('🚀 mini ' . $type . ' server [' . $this->worker_num . ' workers] running：' . $this->config['ip'] . ':' . $this->config['port'] . '...');
        Listener::getInstance()->listen('start', $server);
        if (config('app.hot_reload', false) && config('app.env', 'local') !== 'production') {
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
            Bootstrap::getInstance()->workerStart($server, $workerId);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
    }

    /**
     * @param Server $server
     * @throws Throwable
     */
    public function onManagerStart(Server $server): void
    {
        $type = $this->type ?: $this->key;
        Command::infoWithTime('🚀 mini ' . $type . ' server [' . $this->worker_num . ' workers] running：' . $this->config['ip'] . ':' . $this->config['port'] . '...');
        Listener::getInstance()->listen('managerStart', $server);
        if (config('app.hot_reload', false) && config('app.env', 'local') !== 'production') {
            Runner::start();
        }
    }

    public function onRequest(Request $request, Response $response): void
    {
        try {
            Context::set('IsInRequestEvent', true);
            Listener::getInstance()->listen('request', $request, $response);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
    }

    /**
     * @param Server $server
     * @param $fd
     * @param $fromId
     * @param $data
     */
    public function onReceive(Server $server, $fd, $fromId, $data): void
    {
        try {
            Listener::getInstance()->listen('receive', $server, $fd, $fromId, $data);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
    }

    /**
     * @param Server $server
     * @param Server\Task $task
     * @return mixed
     */
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
                    $response = call_user_func_array(\Opis\Closure\unserialize($data['callable'], ["allowed_classes" => true]), (array)$data['params']);
                }
                return $task->finish($response);
            }
            Listener::getInstance()->listen('task', $server);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
        return null;
    }

    /**
     * @param Server $server
     * @param int $task_id
     * @param $data
     */
    public function onFinish(Server $server, int $task_id, $data): void
    {
        try {
            Listener::getInstance()->listen('finish', $server, $task_id, $data);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param Request $request
     */
    public function onOpen(\Swoole\WebSocket\Server $server, Request $request): void
    {
        try {
            Context::set('IsInWebsocketEvent', true);
            Listener::getInstance()->listen('open', $server, $request);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param Frame $frame
     */
    public function onMessage(\Swoole\WebSocket\Server $server, Frame $frame): void
    {
        try {
            Context::set('IsInWebsocketEvent', true);
            Listener::getInstance()->listen('message', $server, $frame);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
    }

    /**
     * @param Server $server
     * @throws Throwable
     */
    public function onShutdown(Server $server): void
    {
        try {
            $type = $this->type ?: $this->key;
            $this->whenServerStop($server);
            Command::errorWithTime('⛔️ mini ' . $type . ' server [' . $this->worker_num . ' workers] stopped.');
            Listener::getInstance()->listen('shutdown', $server);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
    }

    /**
     * @param Server $server
     * @throws Throwable
     */
    public function onBeforeReload(Server $server): void
    {
        try {
            if (!(config('app.hot_reload', false) && config('app.env', 'local') !== 'production')) {
                $type = $this->type ?: $this->key;
                Command::infoWithTime('🔄 mini ' . $type . ' server [' . $this->worker_num . ' workers] reloading.');
            }
            Listener::getInstance()->listen('beforeReload', $server);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
    }

    /**
     * @param Server $server
     * @throws Throwable
     */
    public function onAfterReload(Server $server): void
    {
        try {
            if (!(config('app.hot_reload', false) && config('app.env', 'local') !== 'production')) {
                $type = $this->type ?: $this->key;
                Command::infoWithTime('✅️ mini ' . $type . ' server [' . $this->worker_num . ' workers] reloaded.');
            }
            Listener::getInstance()->listen('afterReload', $server);
        } catch (Throwable $throwable) {
            Handler::getInstance()->throw($throwable);
        }
    }


    /**
     * @param Server $server
     * @param int $workerId
     * @throws Throwable
     */
    public function onWorkerStop(Server $server, int $workerId): void
    {
        run(function () use ($server, $workerId) {
            try {
                Listener::getInstance()->listen('workerStop', $server, $workerId);
            } catch (Throwable $throwable) {
                Handler::getInstance()->throw($throwable);
            }
        });
    }

    /**
     * @param Server $server
     * @param int $workerId
     * @throws Throwable
     */
    public function onWorkerExit(Server $server, int $workerId): void
    {
        run(function () use ($server, $workerId) {
            try {
                Listener::getInstance()->listen('workerExit', $server, $workerId);
            } catch (Throwable $throwable) {
                Handler::getInstance()->throw($throwable);
            }
        });
    }

    /**
     * @param Server $server
     */
    private function whenServerStop(Server $server): void
    {
        //socket 处理
        $app = app();
        if ($app->has('dcs')) {
            $redis = Redis::connection(config('cache.drivers.redis.collection', 'cache'));
            $it = NULL;
            while ($keys = $redis->scan($it, 'socket:*')) {
                is_array($keys) && $redis->unlink($keys);
            }
        }
        // 连接池关闭
        $app->has('redis') && app('redis')->closePool();
        $app->has('db') && app('db')->closePool();
        $app->has('db.mini.pool') && app('db.mini.pool')->closePool();
    }


    /**
     * @param $error_message
     * @param int|string $code
     * @return string
     */
    protected function error($error_message, int|string $code = 0): string
    {
        return json_encode([
            'code' => $code,
            'message' => $error_message,
        ], JSON_UNESCAPED_UNICODE);
    }
}
