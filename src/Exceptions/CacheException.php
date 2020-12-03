<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exceptions;

use Exception;
use Psr\SimpleCache\CacheException as PsrCacheException;

/**
 * Class CacheException
 * @package Mini\Exceptions
 */
class CacheException extends Exception implements PsrCacheException
{
}