<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use App\Exceptions\Handler;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Service\AbstractServiceProvider;

/**
 * Class ExceptionServiceProvider
 * @package Mini\Exception
 */
class ExceptionServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('exception', function () {
            return class_exists(Handler::class) ? Handler::getInstance() : \Mini\Exception\Handler::getInstance();
        });
    }

    public function boot(): void
    {
        //
    }
}
