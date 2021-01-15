<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Closure;
use Mini\Console\Panel;
use Mini\Container\Container;
use Mini\Contracts\ServiceProviderInterface;
use Mini\Exceptions\HttpException;
use Mini\Service\Server\CustomServer;
use Mini\Service\Server\HelpServer;
use Mini\Service\Server\HttpServer;
use Mini\Service\Server\MiniServer;
use Mini\Service\Server\StopServer;
use Mini\Service\Server\WebSocket;
use Mini\Service\Server\MqttServer;
use Mini\Service\Server\MainServer;
use Mini\Service\Server\WsHttpServer;
use Mini\Support\Arr;
use Mini\Support\Command;
use Mini\Support\Str;
use RuntimeException;

class Application extends Container
{
    /**
     * version
     * @var string
     */
    public static string $version = '2.0.1';

    /**
     * @var array|string[]
     */
    public static array $mapping = [
        'http' => HttpServer::class,
        'ws' => WebSocket::class,
        'wshttp' => WsHttpServer::class,
        'mqtt' => MqttServer::class,
        'main' => MainServer::class,
        'help' => HelpServer::class,
        'all' => MiniServer::class
    ];

    /**
     * @var string
     */
    protected static string $default = HttpServer::class;

    public static function welcome(): void
    {
        $version = self::$version;
        $info = <<<EOL
 _______ _____ __   _ _____
 |  |  |   |   | \  |   |  
 |  |  | __|__ |  \_| __|__   $version \n
EOL;
        Command::line($info);
        $data = [
            'App Information' => [
                'Name' => env('APP_NAME', 'Mini App'),
                'Env' => ucfirst(env('APP_ENV', 'local')),
                'Timezone' => ini_get('date.timezone'),
            ],
            'System Information' => [
                'OS' => PHP_OS . '-' . php_uname('r') . '-' . php_uname('m'),
                'PHP' => PHP_VERSION,
                'Swoole' => SWOOLE_VERSION,
            ],
        ];
        Panel::show($data, '');
    }

    /**
     * run application
     */
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
            $key = $argv[2] ?? 'http';
            $server = static::$mapping[$key] ?? CustomServer::class;
            new $server($key);
        }
    }

    private static function initial(): void
    {
        ini_set('display_errors', config('app.debug') === true ? 'on' : 'off');
        ini_set('display_startup_errors', 'on');
        ini_set('date.timezone', config('app.timezone', 'UTC'));
//        error_reporting(env('APP_ENV', 'local') === 'production' ? 0 : E_ALL);
        error_reporting(E_ALL);
    }
}
