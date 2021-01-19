<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Bootstrap\Middleware;
use Mini\Bootstrap\ProviderService;
use Mini\Command\CommandService;
use SeasLog;
use Swoole\Server;
use Throwable;

/**
 * Class Bootstrap
 * @package Mini
 */
class Bootstrap
{
    use Singleton;

    public static function initial(): void
    {
        ini_set('display_errors', config('app.debug') === true ? 'on' : 'off');
        ini_set('display_startup_errors', 'on');
        ini_set('date.timezone', config('app.timezone', 'UTC'));
    }

    /**
     * Bootstrap constructor.
     * @throws Contracts\Container\BindingResolutionException
     */
    private function __construct()
    {
        app()->singleton('middleware', function () {
            return new Middleware(config('app.middleware', []));
        });
        app()->singleton('providers', function () {
            return new ProviderService(config('app.providers', []));
        });
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws Throwable
     */
    public function workerStart(?Server $server, ?int $workerId): void
    {
        app('providers')->bootstrap($server, $workerId);
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }

    public function consoleStart(): void
    {
        SeasLog::setRequestID(uniqid('', true));
        app('providers')->bootstrap();
    }
}