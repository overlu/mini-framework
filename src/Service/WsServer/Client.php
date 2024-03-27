<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Support\Store;
use Swoole\Coroutine\Http\Client as SwooleClient;

/**
 * Class Client
 * @package Mini\Service\WsServer
 */
class Client
{
    /**
     * @var SwooleClient[]
     */
    private array $clients = [];
    public string $path;

    public function __construct()
    {
        $this->path = DCS::generateUrlPath();
    }

    /**
     * @param array $client
     * @param string $dcs_action
     * @param mixed $arrData
     */
    public function push(array $client, string $dcs_action, mixed $arrData = []): void
    {
        $data = json_encode([
            'fd' => $client['fd'],
            'uid' => $client['uid'],
            'dcs_action' => $dcs_action,
            'data' => $arrData
        ]);
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
     * 获取本地socket服务地址
     * @return string
     */
    public static function localClientHost(): string
    {
        return config('websocket.host', '127.0.0.1') . ':' . config('websocket.port', '9501');
    }

    /**
     * 注册
     */
    public static function register(): void
    {
        Store::put(Socket::$host, self::localClientHost());
    }

    /**
     * 注销
     */
    public static function unregister(): void
    {
        Store::remove(Socket::$host, self::localClientHost());
    }
}
