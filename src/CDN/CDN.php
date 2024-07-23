<?php
/**
 * This file is part of mini-framework.
 * @auth lupeng
 * @date 2024/7/23 下午2:58
 */
declare(strict_types=1);

namespace Mini\CDN;

use Mini\Exception\CDNDriverNotExistException;

class CDN
{
    /**
     * @param string $driver
     * @return AbstractCDN
     * @throws CDNDriverNotExistException
     */
    public function driver(string $driver): AbstractCDN
    {
        $app = app();
        if (!$app->bound('cdn.drivers.' . $driver)) {
            throw new CDNDriverNotExistException("CDN driver '{$driver}' not found.");
        }
        return $app->make('cdn.drivers.' . $driver);
    }

    /**
     * @param string $driver
     * @param mixed $closure
     * @return void
     * @throws CDNDriverNotExistException
     */
    public function extend(string $driver, mixed $closure): void
    {
        $app = app();
        if (!$app->bound('cdn.drivers.' . $driver)) {
            throw new CDNDriverNotExistException("CDN driver '{$driver}' already existed.");
        }
        $app->singleton('cdn.drivers.' . $driver, function () use ($driver, $closure) {
            $callback = call($closure);
            if (!$callback instanceof AbstractCDN) {
                throw new CDNDriverNotExistException("CDN driver '{$driver}' not found");
            }
        });
    }
}