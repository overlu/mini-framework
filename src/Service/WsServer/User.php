<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use JsonException;
use Mini\Facades\Cache;
use Mini\Support\Store;

/**
 * Class User
 * @package Mini\Service\WsServer
 */
class User
{
    private static string $prefix = 'socket_uid:';

    /**
     * 绑定用户
     * @param string $uid
     * @param int $fd
     * @return array
     * @throws JsonException
     */
    public static function bind(string $uid, int $fd): array
    {
        Cache::driver(config('websocket.cache_driver', 'redis'))->set('socket_fd:' . $fd, $uid);
        $clientIds = Store::put(static::$prefix . $uid, Socket::packClientId($fd), config('websocket.max_num_of_uid_online', 0));
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
        return Store::put(static::$prefix . 'all', $uid);
    }

    /**
     * 判断是否绑定全局
     * @param string $uid
     * @return bool
     */
    public static function joined(string $uid): bool
    {
        return Store::has(static::$prefix . 'all', $uid);
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
        $client = Socket::packClientId($fd);
        Store::remove(static::$prefix . $uid, $client);
        Cache::driver(config('websocket.cache_driver', 'redis'))->delete('socket_fd:' . $fd);
        if (empty(static::getFds($uid))) {
            self::leaveOut($uid);
        }
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
        Store::drop(static::$prefix . $uid);
        Group::unbind($uid);
        return Store::remove(static::$prefix . 'all', $uid);
    }


    /**
     * 获取绑定用户的客户端
     * @param string $uid
     * @return array
     */
    public static function getFds(string $uid): array
    {
        return Store::get(static::$prefix . $uid);
    }

    /**
     * 根据fd获取用户id
     * @param $fd
     * @return mixed
     */
    public static function getUserByFd($fd)
    {
        return Cache::driver(config('websocket.cache_driver', 'redis'))->get('socket_fd:' . $fd);
    }

    /**
     * 获取所有的用户
     * @return array uid[]
     */
    public static function getAll(): array
    {
        return Store::get(static::$prefix . 'all');
    }

    /**
     * 获取所有的用户客户端
     * @return array
     */
    public static function getAllFds(): array
    {
        $ids = static::getAll();
        $fds = [];
        foreach ($ids as $uid) {
            $fds[] = [...$fds, ...static::getFds($uid)];
        }
        return $fds;
    }

    /**
     * 判断用户是否在线
     * @param string $uid
     * @return bool
     */
    public static function isOnline(string $uid): bool
    {
        return Store::has(static::$prefix . 'all', $uid);
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
        return count(static::getFds($uid));
    }

    public static function getUserGroups(string $uid): array
    {
        return Store::get(Group::$user_prefix . $uid);
    }
}