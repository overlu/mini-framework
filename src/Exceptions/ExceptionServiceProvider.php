<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exceptions;

use App\Exceptions\Handler;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

/**
 * Class ExceptionServiceProvider
 * @package Mini\Exceptions
 */
class ExceptionServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws BindingResolutionException
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        app()->singleton('exception', function () {
            return class_exists(Handler::class) ? Handler::getInstance() : \Mini\Exceptions\Handler::getInstance();
        });
    }

    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        // TODO: Implement boot() method.
    }
}
