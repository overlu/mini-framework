<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exceptions;

use Exception;
use JsonException;
use Mini\Context;
use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Logging\Log;
use Mini\Support\Command;
use Seaslog;
use Swoole\ExitException;
use Throwable;

/**
 * Class Handler
 * @package Mini\Exceptions
 */
class Handler implements HandlerInterface
{
    protected bool $debug = false;

    protected string $environment = 'production';

    protected array $dontReport = [];

    protected Throwable $throwable;

    protected array $exceptionHeaders = [
        'content-type' => 'application/json;charset=UTF-8',
        'server' => 'mini',
    ];

    public function __construct()
    {
        $this->environment = env('APP_ENV', 'production');
        $this->debug = env('APP_DEBUG', false);
    }

    /**
     * @param Throwable $throwable
     * @throws InvalidResponseException
     * @throws Throwable
     * @throws JsonException
     */
    public function throw(Throwable $throwable): void
    {
        if ($throwable instanceof HttpException) {
            $this->sendException($throwable);
        } else if ($this->environment !== 'production') {
            if (Context::has('IsInRequestEvent')) {
                $this->render(request(), $throwable);
            }
            $this->report($throwable);
        } else {
            abort(500);
        }

    }

    /**
     * @param Throwable $throwable
     * @throws Throwable
     */
    public function report(Throwable $throwable): void
    {
        if ($this->checkNotDontReport($throwable) && !$throwable instanceof ExitException) {
            Command::line();
            Command::error($this->formatException($throwable));
            Command::line();
        }
    }

    /**
     * @param RequestInterface $request
     * @param Throwable $throwable
     * @throws InvalidResponseException
     */
    public function render(RequestInterface $request, Throwable $throwable): void
    {
        if ($this->checkNotDontReport($throwable)) {
            abort(500, $this->formatResponseException($throwable));
            Log::error($this->format($throwable));
        }
    }

    /**
     * @param Throwable $throwable
     * @return mixed
     */
    protected function formatException(Throwable $throwable)
    {
        if ($this->debug) {
            return $throwable;
        }
        return "Whoops, something error";
    }

    /**
     * @param Throwable $throwable
     * @return string|array
     */
    protected function formatResponseException(Throwable $throwable)
    {
        if ($this->debug) {
            return $this->format($throwable);
        }
        return 'Whoops, something error.';
    }

    /**
     * @param Throwable $throwable
     * @return array
     */
    protected function format(Throwable $throwable): array
    {
        return [
            'exception' => get_class($throwable),
            'exception message' => $throwable->getMessage() . ' in ' . $throwable->getFile() . ':' . $throwable->getLine(),
            'exception trace detail' => $throwable->getTrace()
        ];
    }

    /**
     * @param Throwable $throwable
     * @return bool
     */
    private function checkNotDontReport(Throwable $throwable): bool
    {
        foreach ($this->dontReport as $throw) {
            if ($throwable instanceof $throw) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Throwable $throwable
     * @throws JsonException
     * @throws Exception
     */
    protected function sendException(Throwable $throwable): void
    {
        if (Context::has('IsInRequestEvent') && $swResponse = response()->getSwooleResponse()) {
            $code = method_exists($throwable, 'getStatusCode') ? $throwable->getStatusCode() : $throwable->getCode();
            $content = [
                'message' => $throwable->getMessage(),
                'code' => $throwable->$code
            ];
            $swResponse->status($code);
            $headers = array_merge(
                [
                    'mini-request-id' => Seaslog::getRequestID()
                ],
                $this->exceptionHeaders,
                method_exists($throwable, 'getHeaders') ? $throwable->getHeaders() : []
            );
            foreach ($headers as $header => $value) {
                $swResponse->setHeader($header, $value, true);
            }
            $swResponse->end(json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        }
    }
}