<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Closure;
use Mini\Facades\Cache;
use Mini\Facades\Redis;

class Store
{
    private static string $lockPrefix = '69014g$9_store_lock:';
    private static string $prefix = '69014g$9_store:';


    /**
     * 获取数据仓库
     * @param string $key
     * @param Closure|null $callback
     * @return array
     */
    public static function get(string $key, Closure $callback = null): array
    {
        $key = self::getKey($key);
        return (array)(Cache::has($key) ? (Cache::get($key, [])) : Cache::remember($key, $callback));
    }

    /**
     * 加入数据
     * @param string $key
     * @param $value
     * @param int $length
     * @return array
     */
    public static function put(string $key, $value, int $length = 0): array
    {
        $redis = Redis::connection(config('cache.drivers.redis.collection', 'cache'));
        $notLocked = $redis->set(static::$lockPrefix . $key, 1, array('nx', 'ex' => 5));
        if ($notLocked) {
            $values = static::get($key);
            $new_values = array_unique([...$values, ...(array)$value]);
            $remove_values = [];
            if ($length && ($remove_length = count($new_values) - $length) > 0) {
                for ($i = 0; $i < $remove_length; $i++) {
                    $remove_values[] = array_shift($new_values);
                }
            }
            Cache::set(self::getKey($key), $new_values);
            $redis->del(static::$lockPrefix . $key);
            return [
                'remove_values' => $remove_values,
                'new_values' => $new_values
            ];
        }

        return [];

    }

    /**
     * 清空仓库
     * @param string $key
     * @return bool
     */
    public static function drop(string $key): bool
    {
        return Cache::delete(self::getKey($key));
    }

    /**
     * 判断是否含有数据
     * @param string $key
     * @param $value
     * @return bool
     */
    public static function has(string $key, $value): bool
    {
        return in_array($value, static::get($key), true);
    }

    /**
     * 移除数据
     * @param string $key
     * @param $value
     * @return bool
     */
    public static function remove(string $key, $value): bool
    {
        $values = static::get($key);
        $index = array_search($value, $values, true);
        if ($index !== false) {
            unset($values[$index]);
            return Cache::set(self::getKey($key), [...$values]);
        }
        return false;
    }

    /**
     * @param string $key
     * @return string
     */
    private static function getKey(string $key): string
    {
        return static::$prefix . $key;
    }
}