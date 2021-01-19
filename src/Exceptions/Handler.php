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
use Mini\Singleton;
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
    use Singleton;

    protected bool $debug = false;

    protected string $environment = 'production';

    protected array $dontReport = [];

    protected array $exceptionHeaders = [
        'content-type' => 'application/json;charset=UTF-8',
        'server' => 'mini',
    ];

    private function __construct()
    {
        $this->environment = env('APP_ENV', 'production');
        $this->debug = env('APP_DEBUG', false);
    }

    /**
     * @param Throwable $throwable
     * @throws Throwable
     * @throws JsonException
     */
    public function throw(Throwable $throwable): void
    {
        if ($throwable instanceof HttpException) {
            $this->sendException($throwable);
            return;
        }
        if (Context::has('IsInRequestEvent')) {
            try {
                $this->render(request(), $throwable);
            } catch (Throwable $throwable) {
                Context::destroy('IsInRequestEvent');
            }
        }
        $this->report($throwable);
    }

    /**
     * @param Throwable $throwable
     * @throws Throwable
     */
    public function report(Throwable $throwable): void
    {
        if (!$throwable instanceof ExitException && $this->checkNotDontReport($throwable)) {
            $this->logError($throwable);
            Command::line();
            Command::error($this->environment !== 'production' ? $this->formatException($throwable) : 'server is busy.');
            Command::line();
        }
    }

    /**
     * @param Throwable $throwable
     */
    public function logError(Throwable $throwable): void
    {
        Log::error($this->format($throwable));
    }

    /**
     * @param RequestInterface $request
     * @param Throwable $throwable
     * @throws JsonException
     */
    public function render(RequestInterface $request, Throwable $throwable): void
    {
        if ($this->checkNotDontReport($throwable)) {
            $this->sendException($throwable);
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
        return "whoops, something error";
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
        return 'whoops, something error.';
    }

    /**
     * @param Throwable $throwable
     * @return array
     */
    protected function format(Throwable $throwable): array
    {
        return [
            'exception' => get_class($throwable),
            'exception code' => $throwable->getCode(),
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
            if ($throwable instanceof HttpExceptionInterface) {
                $code = $throwable->getStatusCode();
                $content = [
                    'code' => $code,
                    'message' => $throwable->getResponseMessage(),
                ];
            } else {
                $content = $this->formatResponseException($throwable);
                $code = 500;
            }
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