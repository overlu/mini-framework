<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exceptions;

/**
 * Interface HttpExceptionInterface
 * @package Mini\Exceptions
 */
interface HttpExceptionInterface extends \Throwable
{
    public function getStatusCode();

    public function getHeaders(): array;

    public function getResponseMessage();
}