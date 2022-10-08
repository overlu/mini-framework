<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use JsonException;
use Mini\Support\Store;

/**
 * Class User
 * @package Mini\Service\WsServer
 */
class User
{
    /**
     * 绑定用户
     * @param string $uid
     * @param int $fd
     * @return array
     * @throws JsonException
     */
    public static function bind(string $uid, int $fd): array
    {
        $fdc = Socket::packFd($fd);
        $maxUidOnline = config('websocket.max_num_of_uid_online', 0);
        Store::put(Socket::$fdPrefix . $fdc, $uid, $maxUidOnline);
        $clientIds = Store::put(Socket::$userPrefix . $uid, Socket::packClientId($uid, $fd), $maxUidOnline);
        if (!static::joined($uid)) {
            static::joinIn($uid);
        }
        if (!empty($clientIds['remove_values'])) {
            Socket::close($clientIds['remove_values']);
        }
        return $clientIds;
    }

    /**
     * 绑定全局
     * @param string $uid
     * @return array
     */
    public static function joinIn(string $uid): array
    {
        Group::bind($uid, Client::localClientHost());
        return Store::put(Socket::$userPrefix . 'all', $uid);
    }

    /**
     * 判断是否绑定全局
     * @param string $uid
     * @return bool
     */
    public static function joined(string $uid): bool
    {
        return Store::has(Socket::$userPrefix . 'all', $uid);
    }

    /**
     * 解绑用户
     * @param string $uid
     * @param int $fd
     * @return bool
     * @throws JsonException
     */
    public static function unbind(string $uid, int $fd): bool
    {
        /**
         * 打包客户端标识
         */
        $client = Socket::packClientId($uid, $fd);
        /**
         * 移除用户中的客户端
         */
        Store::remove(Socket::$userPrefix . $uid, $client);
        /**
         * 删除（客户端-用户）缓存
         */
        Store::drop(Socket::$fdPrefix . Socket::packFd($fd));
        if (empty(static::getClients($uid))) {
            self::leaveOut($uid);
        }
        /**
         * 断开链接
         */
        Socket::close($client);
        return true;
    }

    /**
     * 解绑全局
     * @param string $uid
     * @return bool
     */
    public static function leaveOut(string $uid): bool
    {
        Store::drop(Socket::$userPrefix . $uid);
        Group::unbind($uid);
        return Store::remove(Socket::$userPrefix . 'all', $uid);
    }


    /**
     * 获取绑定用户的客户端
     * @param string $uid
     * @return array
     */
    public static function getClients(string $uid): array
    {
        return Store::get(Socket::$userPrefix . $uid);
    }

    /**
     * 根据fd获取用户id
     * @param $fd
     * @return mixed
     */
    public static function getUserByFd($fd)
    {
        return Store::get(Socket::$fdPrefix . Socket::packFd($fd));
    }

    /**
     * 获取所有的用户
     * @return array uid[]
     */
    public static function getAll(): array
    {
        return Store::get(Socket::$userPrefix . 'all');
    }

    /**
     * 获取所有的用户客户端
     * @return array
     */
    public static function getAllClients(): array
    {
        $ids = static::getAll();
        $clients = [];
        foreach ($ids as $uid) {
            $clients[] = [...$clients, ...static::getClients($uid)];
        }
        return $clients;
    }

    /**
     * 判断用户是否在线
     * @param string $uid
     * @return bool
     */
    public static function isOnline(string $uid): bool
    {
        return Store::has(Socket::$userPrefix . 'all', $uid);
    }

    /**
     * 获取在线用户
     * @return array uid[]
     */
    public static function getOnlineUsers(): array
    {
        return static::getAll();
    }

    /**
     * 获取用户在线设备数
     * @param string $uid
     * @return int
     */
    public static function getUserFdNum(string $uid): int
    {
        return count(static::getClients($uid));
    }

    public static function getUserGroups(string $uid): array
    {
        return Store::get(Socket::$userGroupPrefix . $uid);
    }
}
