<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use ArrayAccess;
use Exception;
use Mini\Config\LoadConfiguration;
use Mini\Config\Repository;
use Mini\Contracts\Config as ConfigContract;
use Symfony\Component\Finder\Finder;

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
