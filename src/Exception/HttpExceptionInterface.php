<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

/**
 * Interface HttpExceptionInterface
 * @package Mini\Exception
 */
interface HttpExceptionInterface extends \Throwable
{
    public function getStatusCode(): int;

    public function getHeaders(): array;

    public function getResponseMessage();
}