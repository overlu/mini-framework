<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Singleton;
use Mini\Support\Store;
use Swoole\Coroutine\Http\Client as SwooleClient;

class Client
{
    use Singleton;

    /**
     * @var SwooleClient[]
     */
    private array $clients = [];

    private function __construct()
    {
        $this->register();
    }

    /**
     * @param $client
     * @param $dcs_action
     * @param array $arrData
     * @throws \JsonException
     */
    public function push(array $client, string $dcs_action, $arrData = []): void
    {
        $data = json_encode([
            'fd' => $client['fd'],
            'dcs_action' => $dcs_action,
            'data' => $arrData
        ], JSON_THROW_ON_ERROR);
        $status = $this->getClient($client['host'], $client['port'])->push($data);
        if (!$status) {
            $this->setClient($client['host'], $client['port']);
            $this->getClient($client['host'], $client['port'])->push($data);
        }
    }

    /**
     * 获取客户端
     * @param string $host
     * @param string $port
     * @return SwooleClient
     */
    public function getClient(string $host, string $port): SwooleClient
    {
        if (empty($this->clients[$host . ':' . $port]) || !$this->clients[$host . ':' . $port] instanceof SwooleClient) {
            $this->setClient($host, $port);
        }
        return $this->clients[$host . ':' . $port];
    }

    /**
     * @param string $host
     * @param string $port
     */
    private function setClient(string $host, string $port): void
    {
        $this->clients[$host . ':' . $port] = new SwooleClient($host, (int)$port);
        $this->clients[$host . ':' . $port]->upgrade(DCS::generateUrlPath());
    }

    /**
     * 注册
     */
    private function register(): void
    {
        Store::put('websocket_server_hosts', config('websocket.host', '127.0.0.1') . ':' . config('websocket.port', '9501'));
        $this->path = DCS::generateUrlPath();
    }
}