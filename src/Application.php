<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Console\Panel;
use Mini\Service\Server\CustomServer;
use Mini\Service\Server\HelpServer;
use Mini\Service\Server\HttpServer;
use Mini\Service\Server\MiniServer;
use Mini\Service\Server\StopServer;
use Mini\Service\Server\WebSocket;
use Mini\Service\Server\MqttServer;
use Mini\Service\Server\MainServer;
use Mini\Service\Server\WsHttpServer;
use Mini\Support\Command;

class Application
{
    /**
     * version
     * @var string
     */
    public static string $version = '2.8.7';

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
        Command::line(<<<EOL
 _______ _____ __   _ _____
 |  |  |   |   | \  |   |  
 |  |  | __|__ |  \_| __|__
EOL. '   ' . self::$version . PHP_EOL);
        Panel::show([
            'App Information' => [
                'Name' => config('app.name', 'Mini'),
                'Env' => ucfirst(config('app.env', 'local')),
                'Timezone' => ini_get('date.timezone'),
            ],
            'System Information' => [
                'OS' => PHP_OS . '-' . php_uname('r') . '-' . php_uname('m'),
                'PHP' => PHP_VERSION,
                'Swoole' => SWOOLE_VERSION,
            ],
        ], '');
    }

    /**
     * run application
     */
    public static function run(): void
    {
        global $argv;
        Bootstrap::initial();
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
}
