<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

class CustomServer extends AbstractServer
{
    public function initialize(): void
    {
        $this->worker_num = $this->config['settings']['worker_num'] ?? 1;
        $serverClass = $this->config['class_name'] ?? \Swoole\Server::class;
        $this->server = new $serverClass(
            $this->config['ip'],
            $this->config['port'],
            $this->config['mode'] ?? SWOOLE_PROCESS,
            $this->config['sock_type'] ?? SWOOLE_SOCK_TCP
        );
        if (isset($this->config['process']) && !empty($this->config['process'])) {
            foreach ($this->config['process'] as $processItem) {
                [$class, $func] = $processItem;
                $this->server->addProcess($class::$func($this->server));
            }
        }
        if (isset($this->config['sub']) && !empty($this->config['sub'])) {
            foreach ($this->config['sub'] as $item) {
                $sub_server = $this->server->addListener($item['ip'], $item['port'], $item['sock_type'] ?? SWOOLE_SOCK_TCP);
                if (isset($item['settings'])) {
                    $sub_server->set($item['settings']);
                }
                foreach ($item['callbacks'] as $eventKey => $callbackItem) {
                    [$class, $func] = $callbackItem;
                    $sub_server->on($eventKey, [$class, $func]);
                }
            }
        }
    }
}
