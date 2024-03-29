<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;

/**
 * Class Socket
 * @package Mini\Service\WsServer
 */
class Socket
{
    public static string $userPrefix = 'socket:uid:';
    public static string $fdPrefix = 'socket:fd:';
    public static string $groupPrefix = 'socket:group:';
    public static string $userGroupPrefix = 'socket:user:group:';
    public static string $host = 'socket:hosts';

    private static function pushByAntherServer($client, $data): void
    {
        app('dcs')->push($client, 'push', $data);
    }

    private static function closeByAntherServer($client): void
    {
        app('dcs')->push($client, 'close');
    }

    /**
     * 推送给用户
     * @param string $uid
     * @param $data
     */
    public static function pushToUser(string $uid, $data): void
    {
        $clients = User::getClients($uid);
        $server = server();
        foreach ($clients as $client) {
            if (is_string($client)) {
                $clientArr = static::unPackClientId($client);
                if ($clientArr['host'] === config('websocket.host') && $clientArr['port'] === config('websocket.port')) {
                    if ($server->exist($clientArr['fd']) && $server->isEstablished($clientArr['fd'])) {
                        $server->push($clientArr['fd'], static::transferToResponse($data));
                    } else {
                        User::unbind($uid, (int)$clientArr['fd']);
                    }
                } else {
                    static::pushByAntherServer($clientArr, $data);
                }
            }
        }
    }

    /**
     * 推送给组
     * @param string $group
     * @param $data
     */
    public static function pushToGroup(string $group, $data): void
    {
        $users = Group::getUsers($group);
        foreach ($users as $user) {
            static::pushToUser($user, $data);
        }
    }

    /**
     * 推送全局
     * @param $data
     */
    public static function pushToAll($data): void
    {
        $users = User::getAll();
        foreach ($users as $user) {
            static::pushToUser($user, $data);
        }
    }

    /**
     * @param $clients
     * @param bool $onlyLocal
     */
    public static function close($clients, bool $onlyLocal = false): void
    {
        $clients = (array)$clients;
        foreach ($clients as $client) {
            $clientArr = static::unPackClientId($client);
            if ($clientArr['host'] === config('websocket.host') && $clientArr['port'] === config('websocket.port')) {
                $server = server();
                if ($server->exist($clientArr['fd']) && $server->isEstablished($clientArr['fd'])) {
                    $server->close($clientArr['fd']);
                }
            } else {
                !$onlyLocal && static::closeByAntherServer($clientArr);
            }
        }
    }

    /**
     * 格式化数据
     * @param $response
     * @return false|string
     */
    public static function transferToResponse($response)
    {
        if ($response instanceof Arrayable) {
            $response = $response->toArray();
        }
        if (is_array($response)) {
            return json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        if ($response instanceof Jsonable) {
            return $response->toJson();
        }
        if (is_object($response)) {
            return method_exists($response, '__toString') ? (string)$response : json_encode((array)$response, JSON_UNESCAPED_UNICODE);
        }
        return (string)$response;
    }

    /**
     * 打包生成链接id
     * @param string $uid
     * @param $fd
     * @return string
     */
    public static function packClientId(string $uid, $fd): string
    {
        return base64_encode(json_encode([
            'uid' => $uid,
            'host' => config('websocket.host'),
            'port' => config('websocket.port'),
            'fd' => $fd
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param $fd
     * @return string
     */
    public static function packFd($fd): string
    {
        return base64_encode(json_encode([
            'host' => config('websocket.host'),
            'port' => config('websocket.port'),
            'fd' => $fd
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 解包
     * @param string $fdc
     * @return array
     */
    public static function unPackFd(string $fdc): array
    {
        return json_decode(base64_decode($fdc), true);
    }

    /**
     * 解包链接客户端
     * @param string $client
     * @return array
     */
    public static function unPackClientId(string $client): array
    {
        return json_decode(base64_decode($client), true);
    }
}
