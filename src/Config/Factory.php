<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Config;

use ArrayAccess;
use Exception;
use Mini\Contracts\Config\Repository as ConfigContract;

/**
 * Class Config
 * @package Mini
 */
class Factory implements ArrayAccess, ConfigContract
{
    use LoadConfiguration, Repository;

    /**
     * Config constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->bootstrap();
    }
}
