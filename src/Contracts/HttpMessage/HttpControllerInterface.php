<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\HttpMessage;

interface HttpControllerInterface
{
    /**
     * @param mixed $data
     * @param string $success_message
     * @param int $code
     * @return array
     */
    public function success(mixed $data = [], string $success_message = 'succeed', int $code = 200): array;

    /**
     * @param string $error_message
     * @param int $code
     * @param mixed $data
     * @return array
     */
    public function failed(string $error_message = 'failed', int $code = 0, mixed $data = []): array;

    /**
     * @param string $method
     * @return mixed
     */
    public function beforeDispatch(string $method): mixed;

    /**
     * @param $response
     * @return mixed
     */
    public function afterDispatch($response): mixed;
}
