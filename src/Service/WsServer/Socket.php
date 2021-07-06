<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use JsonException;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;

/**
 * Class Socket
 * @package Mini\Service\WsServer
 */
class Socket
{
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
     * @throws JsonException
     */
    public static function pushToUser(string $uid, $data): void
    {
        $clients = User::getFds($uid);
        $server = server();
        foreach ($clients as $client) {
            $clientArr = static::unPackClientId($client);
            if ($clientArr['host'] === config('websocket.host') && $clientArr['port'] === config('websocket.port')) {
                if ($server->exist($clientArr['fd']) && $server->isEstablished($clientArr['fd'])) {
                    $server->push($clientArr['fd'], static::transferToResponse($data));
                }
            } else {
                static::pushByAntherServer($clientArr, $data);
            }
        }
    }

    /**
     * 推送给组
     * @param string $group
     * @param $data
     * @throws JsonException
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
     * @throws JsonException
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
     * @throws JsonException
     */
    public static function close($clients): void
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
                static::closeByAntherServer($clientArr);
            }
        }
    }

    /**
     * 格式化数据
     * @param $response
     * @return false|string
     * @throws JsonException
     */
    public static function transferToResponse($response)
    {
        if ($response instanceof Arrayable) {
            $response = $response->toArray();
        }
        if (is_array($response)) {
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        if ($response instanceof Jsonable) {
            return $response->toJson();
        }
        if (is_object($response)) {
            return method_exists($response, '__toString') ? (string)$response : json_encode((array)$response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        return (string)$response;
    }

    /**
     * 打包生成链接id
     * @param $fd
     * @return string
     * @throws JsonException
     */
    public static function packClientId($fd): string
    {
        return base64_encode(json_encode([
            'host' => config('websocket.host'),
            'port' => config('websocket.port'),
            'fd' => $fd
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * 解包链接客户端
     * @param string $client
     * @return array
     * @throws JsonException
     */
    public static function unPackClientId(string $client): array
    {
        return json_decode(base64_decode($client), true, 512, JSON_THROW_ON_ERROR);
    }
}