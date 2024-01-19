<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use Mini\Translate\Translate;
use RuntimeException;
use Throwable;

/**
 * Class HttpException
 * @package Mini\Exception
 */
class WebsocketException extends RuntimeException
{

    /**
     * HttpException constructor.
     * @param string $message
     * @param int|null $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', ?int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message ?: app(Translate::class)->getOrDefault('http_status_code.' . $code, 'something error'), $code, $previous);
    }
}