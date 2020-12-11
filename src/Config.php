<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

class Config
{
    use Singleton;

    private static array $config = [];

    private function __construct()
    {
    }

    /**
     * @param $keys
     * @param null $default
     * @return null|mixed
     */
    public function get($keys, $default = null)
    {
        $keys = explode('.', $keys);
        if (empty($keys)) {
            return null;
        }

        $file = array_shift($keys);

        if (empty(self::$config[$file])) {
            if (!is_file($config_file = CONFIG_PATH . $file . '.php')) {
                return null;
            }
            self::$config[$file] = include $config_file;
        }
        $config = self::$config[$file];
        while ($keys) {
            $key = array_shift($keys);
            if (!isset($config[$key])) {
                $config = $default;
                break;
            }
            $config = $config[$key];
        }
        return $config;
    }
}
