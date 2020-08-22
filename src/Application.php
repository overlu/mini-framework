<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Console\Panel;
use Mini\Service\Server\HelpServer;
use Mini\Service\Server\HttpServer;
use Mini\Service\Server\MiniServer;
use Mini\Service\Server\StopServer;
use Mini\Service\Server\WebSocket;
use Mini\Service\Server\MqttServer;
use Mini\Service\Server\MainServer;
use Mini\Support\Command;

class Application
{
    public static string $version = '1.0.15';

    public static array $mapping = [
        'http' => HttpServer::class,
        'ws' => WebSocket::class,
        'mqtt' => MqttServer::class,
        'main' => MainServer::class,
        'help' => HelpServer::class,
        'all' => MiniServer::class
    ];

    protected static string $default = HttpServer::class;

    public static function welcome(): void
    {
        $appVersion = self::$version;
        $swooleVersion = SWOOLE_VERSION;
        $phpVersion = PHP_VERSION;
        $serverOs = PHP_OS . '-' . php_uname('r') . '-' . php_uname('m');
        $appName = env('APP_NAME', 'Mini App');
        $env = ucfirst(env('APP_ENV', 'local'));
        $timezone = ini_get('date.timezone');
        $info = <<<EOL
     ___  ___   _   __   _   _  
    /   |/   | | | |  \ | | | | 
   / /|   /| | | | |   \| | | | 
  / / |__/ | | | | | |\   | | | 
 / /       | | | | | | \  | | | 
/_/        |_| |_| |_|  \_| |_| \n
EOL;
        Command::line($info);
        $data = [
            'Application name' => "\e[0;32m{$appName}\e[0m",
            'Application Environment' => "\e[0;32m{$env}\e[0m",
            'Mini/PHP/Swoole version' => "\e[0;32m{$appVersion}/{$phpVersion}/{$swooleVersion}\e[0m",
            'OS version' => "\e[0;32m{$serverOs}\e[0m",
            'Timezone' => "\e[0;32m{$timezone}\e[0m",
        ];
        Panel::show($data, '');
    }

    public static function run(): void
    {
        self::initial();
        global $argv;
        self::welcome();
        if (!isset($argv[1]) || !in_array($argv[1], ['start', 'stop'])) {
            new HelpServer();
        }
        if ($argv[1] === 'stop') {
            new StopServer($argv[2] ?? '');
        } else {
            $server = static::$mapping[$argv[2] ?? 'http'] ?? HelpServer::class;
            new $server();
        }
    }

    private static function initial()
    {
        ini_set('display_errors', env('APP_DEBUG') === true ? 'on' : 'off');
        ini_set('display_startup_errors', 'on');
        ini_set('date.timezone', config('mini.timezone', 'UTC'));
//        error_reporting(env('APP_ENV', 'local') === 'production' ? 0 : E_ALL);
        error_reporting(E_ALL);
    }
}
