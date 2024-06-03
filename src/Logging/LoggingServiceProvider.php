<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Logging;

use Mini\Service\AbstractServiceProvider;
use Seaslog;
use Throwable;

class LoggingServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        try {
            $config = config('logging');
            @Seaslog::setBasePath($config['default_base_path']);
            @Seaslog::setLogger($config['default_logger']);
            if (!empty($config['observer']) && class_exists($config['observer']) && $config['observer'] instanceof Observer) {
                foreach (Logger::$cliLevel as $level => $item) {
                    $this->app['events']->listen('logging.' . $level, $config['observer'] . '@' . (method_exists($config['observer'], $level) ? $level : 'handle'));
                }
            }
        } catch (Throwable $throwable) {
            $this->app['exception']->logError($throwable);
        }

    }

    public function boot(): void
    {
        //
    }
}