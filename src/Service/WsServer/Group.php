<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Support\Store;

/**
 * Class Group
 * @package Mini\Service\WsServer
 */
class Group
{
    /**
     * 绑定用户组
     * @param string $group
     * @param string $uid
     * @return array
     */
    public static function bind(string $uid, string $group): array
    {
        $user_groups = Store::put(Socket::$userGroupPrefix . $uid, $group);
        $group_users = Store::put(Socket::$groupPrefix . $group, $uid);
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
            Store::remove(Socket::$userGroupPrefix . $uid, $group);
            Store::remove(Socket::$groupPrefix . $group, $uid);
            if (empty(static::getUsers($group))) {
                Store::drop(Socket::$groupPrefix . $group);
            }
            return true;
        }
        $groups = User::getUserGroups($uid);
        foreach ($groups as $group) {
            Store::remove(Socket::$groupPrefix . $group, $uid);
            if (empty(static::getUsers($group))) {
                Store::drop(Socket::$groupPrefix . $group);
            }
        }
        Store::drop(Socket::$userGroupPrefix . $uid);
        return true;
    }

    /**
     * 获取绑定组的用户
     * @param string $group
     * @return array
     */
    public static function getUsers(string $group): array
    {
        return Store::get(Socket::$groupPrefix . $group);
    }

    /**
     * 获取绑定组的客户端
     * @param string $group
     * @return array
     */
    public static function getClients(string $group): array
    {
        $user_ids = static::getUsers($group);
        $clients = [];
        foreach ($user_ids as $uid) {
            $clients[] = [...$clients, ...User::getClients($uid)];
        }
        return $clients;
    }
}