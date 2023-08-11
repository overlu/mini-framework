<?php
/**
 * This file is part of mini-framework.
 * @auth lupeng
 * @date 2023/8/10 23:00
 */
declare(strict_types=1);

namespace Mini\Contracts\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
}
