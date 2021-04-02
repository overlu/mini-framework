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
     * @param string|null $success_message
     * @param array $data
     * @return array
     */
    public function success(?string $success_message = 'succeed', array $data = []): array;

    /**
     * @param string|null $error_message
     * @param int $code
     * @return array
     */
    public function failed(?string $error_message = 'failed', $code = 0): array;

    /**
     * @param string $method
     * @return mixed
     */
    public function beforeDispatch(string $method);

    /**
     * @param $response
     * @return mixed
     */
    public function afterDispatch($response);
}
