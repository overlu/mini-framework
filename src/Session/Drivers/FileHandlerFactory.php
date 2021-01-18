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
use Mini\Utils\Filesystem\Filesystem;
use Psr\Container\ContainerInterface;

class FileHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get(ConfigInterface::class);
        $path = $config->get('session.options.path');
        $minutes = $config->get('session.options.gc_maxlifetime', 1200);
        if (! $path) {
            throw new \InvalidArgumentException('Invalid session path.');
        }
        return new FileSessionDriver($container->get(Filesystem::class), $path, $minutes);
    }
}
