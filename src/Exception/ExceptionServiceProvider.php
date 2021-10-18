<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use App\Exceptions\Handler;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;
use Swoole\Server;

/**
 * Class ExceptionServiceProvider
 * @package Mini\Exception
 */
class ExceptionServiceProvider extends ServiceProvider
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws BindingResolutionException
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        $this->app->singleton('exception', function () {
            return class_exists(Handler::class) ? Handler::getInstance() : \Mini\Exception\Handler::getInstance();
        });
    }

    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        // TODO: Implement boot() method.
    }
}
