<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Support\Store;

class Group
{
    private static string $prefix = 'socket_group_';
    private static string $user_prefix = 'socket_user_group_';

    /**
     * 绑定用户组
     * @param string $group
     * @param string $uid
     * @return array
     */
    public static function bind(string $uid, string $group): array
    {
        $user_groups = Store::put(static::$user_prefix . $uid, $group);
        $group_users = Store::put(static::$prefix . $group, $uid);
        return [
            'user_groups' => $user_groups,
            'group_users' => $group_users
        ];
    }

    /**
     * 解绑组
     * @param string $group
     * @param string $uid
     * @return bool
     */
    public static function unbind(string $uid, string $group = ''): bool
    {
        if ($group) {
            Store::remove(static::$user_prefix . $uid, $group);
            Store::remove(static::$prefix . $group, $uid);
            if (empty(static::getUsers($group))) {
                Store::drop(static::$prefix . $group);
            }
            return true;
        }
        $groups = User::getUserGroups($uid);
        foreach ($groups as $group) {
            Store::remove(static::$prefix . $group, $uid);
            if (empty(static::getUsers($group))) {
                Store::drop(static::$prefix . $group);
            }
        }
        Store::drop(static::$user_prefix . $uid);
        return true;
    }

    /**
     * 获取绑定组的用户
     * @param string $group
     * @return array
     */
    public static function getUsers(string $group): array
    {
        return Store::get(static::$prefix . $group);
    }

    /**
     * 获取绑定组的客户端
     * @param string $group
     * @return array
     */
    public static function getFds(string $group): array
    {
        $user_ids = static::getUsers($group);
        $fds = [];
        foreach ($user_ids as $uid) {
            $fds[] = [...$fds, ...User::getFds($uid)];
        }
        return $fds;
    }
}