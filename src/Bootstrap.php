<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Bootstrap\Middleware;
use Mini\Bootstrap\ProviderService;
use ReflectionException;
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

    /**
     * Mini Framework Service Providers...
     * @var array
     */
    private array $providers = [
        \Mini\Exception\ExceptionServiceProvider::class,
        \Mini\Config\ConfigServiceProvider::class,
        \Mini\Encryption\EncryptionServiceProvider::class,
        \Mini\Filesystem\FilesystemServiceProvider::class,
        \Mini\Events\EventServiceProvider::class,
        \Mini\Logging\LoggingServiceProvider::class,
        \Mini\Database\Mysql\EloquentServiceProvider::class,
        \Mini\Database\Mini\MiniDBServiceProvider::class,
        \Mini\Database\Redis\RedisServiceProvider::class,
        \Mini\Cache\CacheServiceProviders::class,
        \Mini\Translate\TranslateServiceProvider::class,
        \Mini\Validator\ValidationServiceProvider::class,
        \Mini\Session\SessionServiceProvider::class,
        \Mini\Hashing\HashServiceProvider::class,
        \Mini\View\ViewServiceProvider::class,
        \Mini\Console\ConsoleServiceProvider::class,
        \Mini\Service\Route\RouteServiceProvider::class
    ];

    public static function initial(): void
    {
        ini_set('display_errors', config('app.debug') === true ? 'on' : 'off');
        ini_set('display_startup_errors', 'on');
        ini_set('date.timezone', config('app.timezone', 'UTC'));
    }

    /**
     * @throws Contracts\Container\BindingResolutionException
     * @throws ReflectionException
     */
    private function __construct()
    {
        $this->initMiddleware();
        $this->initProviderService();
    }

    /**
     * @return void
     * @throws Contracts\Container\BindingResolutionException
     * @throws ReflectionException
     */
    public function initMiddleware(): void
    {
        app()->singleton('middleware', function () {
            return new Middleware(config('app.middleware', []));
        });
    }

    /**
     * @return void
     * @throws Contracts\Container\BindingResolutionException
     * @throws ReflectionException
     */
    public function initProviderService(): void
    {
        app()->singleton('providers', function () {
            return new ProviderService([...$this->providers, ...config('app.providers', [])]);
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
        SeasLog::setRequestID(uniqid('mini-artisan-', true));
        app('providers')->bootstrap();
    }
}
