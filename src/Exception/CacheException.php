<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use Exception;
use Psr\SimpleCache\CacheException as PsrCacheException;

/**
 * Class CacheException
 * @package Mini\Exception
 */
class CacheException extends Exception implements PsrCacheException
{
}