<?php

declare(strict_types=1);
/**
 * This file is part of Mini.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Mini\Session\Drivers;

use Mini\Contract\ConfigInterface;
use Mini\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

class RedisHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get(ConfigInterface::class);
        $connection = $config->get('session.options.connection');
        $gcMaxLifetime = $config->get('session.options.gc_maxlifetime', 1200);
        $redisFactory = $container->get(RedisFactory::class);
        $redis = $redisFactory->get($connection);
        return new RedisSessionDriver($redis, $gcMaxLifetime);
    }
}
