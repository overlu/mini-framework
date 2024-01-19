<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Config;

use ArrayAccess;
use Exception;
use Mini\Contracts\Config as ConfigContract;
use Mini\Singleton;

/**
 * Class Config
 * @package Mini
 */
class Config implements ArrayAccess, ConfigContract
{
    use Singleton, LoadConfiguration, Repository;

    /**
     * Config constructor.
     * @throws Exception
     */
    private function __construct()
    {
        $this->bootstrap();
    }
}
