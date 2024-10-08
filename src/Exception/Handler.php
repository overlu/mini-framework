<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use Exception;
use Mini\Context;
use Mini\Contracts\HttpMessage\WebsocketRequestInterface;
use Mini\Contracts\Request as RequestInterface;
use Mini\Logging\Logger;
use Mini\Singleton;
use Mini\Support\Command;
use Mini\Translate\Translate;
use Seaslog;
use Swoole\ExitException;
use Throwable;

/**
 * Class Handler
 * @package Mini\Exception
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
        $this->environment = (string)env('APP_ENV', 'production');
        $this->debug = (bool)env('APP_DEBUG', false);
    }

    /**
     * @param Throwable $throwable
     * @throws Throwable
     */
    public function throw(Throwable $throwable): void
    {
        if ($throwable instanceof DdException) {
            $this->dd();
            return;
        }
        if ($throwable instanceof HttpException) {
            $this->sendHttpException($throwable);
            return;
        }
        if ($throwable instanceof WebsocketException) {
            $this->sendWebsocketException($throwable);
            return;
        }
        if (Context::has('IsInRequestEvent')) {
            $this->render(request(), $throwable);
        }
        if (Context::has('IsInWebsocketEvent')) {
            $this->render(ws_request(), $throwable);
        }
        $this->report($throwable);
    }

    /**
     * @param Throwable $throwable
     * @throws Throwable
     */
    public function report(Throwable $throwable): void
    {
        if (!$throwable instanceof ExitException && !$throwable instanceof DdException && $this->hasNoDontReport($throwable)) {
            $this->logError($throwable);
            Command::line();
            Command::error($this->formatException($throwable));
            Command::line();
        }
    }

    /**
     * @param Throwable $throwable
     */
    public function logError(Throwable $throwable): void
    {
        Logger::error($this->format($throwable), [], 'system');
    }

    /**
     * @param RequestInterface|WebsocketRequestInterface $request
     * @param Throwable $throwable
     * @throws Exception
     */
    public function render(RequestInterface|WebsocketRequestInterface $request, Throwable $throwable): void
    {
        if ($this->hasNoDontReport($throwable)) {
            if (Context::has('IsInRequestEvent')) {
                try {
                    $this->sendHttpException($throwable);
                } catch (Throwable $throwable) {
                    Context::destroy('IsInRequestEvent');
                }
            } else if (Context::has('IsInWebsocketEvent')) {
                try {
                    $this->sendWebsocketException($throwable);
                } catch (Throwable $throwable) {
                    Context::destroy('IsInWebsocketEvent');
                    ws_response()->close();
                }
            }

        }
    }

    /**
     * @param Throwable $throwable
     * @return string|Throwable
     */
    protected function formatException(Throwable $throwable): Throwable|string
    {
        if ($this->debug) {
            return $throwable;
        }
        return "whoops, something error";
    }

    /**
     * @param Throwable $throwable
     * @return array
     */
    protected function formatResponseException(Throwable $throwable): array
    {
        if ($this->environment === 'production') {
            return [
                'code' => 1001,
                'message' => 'server is busy, please wait for a moment.'
            ];
        }
        if ($this->debug) {
            return $this->format($throwable);
        }
        return [
            'code' => 500,
            'message' => 'whoops, something error.'
        ];
    }

    /**
     * @param Throwable $throwable
     * @return array
     */
    protected function format(Throwable $throwable): array
    {
        $res = [
            'exception' => get_class($throwable),
            'exception_code' => $throwable->getCode(),
            'exception_message' => $throwable->getMessage() . ' in ' . $throwable->getFile() . ':' . $throwable->getLine()
        ];
        if (config('debugger.exception.show_trace', false)) {
            $res['exception_trace'] = $throwable->getTrace();
        }
        return $res;
    }

    /**
     * @param Throwable $throwable
     * @return bool
     */
    private function hasNoDontReport(Throwable $throwable): bool
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
     * @throws Exception
     */
    private function sendHttpException(Throwable $throwable): void
    {
        if (Context::has('IsInRequestEvent') && $swResponse = response()->getSwooleResponse()) {
            if ($throwable instanceof HttpExceptionInterface) {
                $code = $throwable->getStatusCode();
                $content = [
                    'code' => $throwable->getCode(),
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
            $swResponse->end(json_encode($content, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * @return void
     */
    private function dd(): void
    {
        if (Context::has('IsInRequestEvent') && $swResponse = response()->getSwooleResponse()) {
            $swResponse->status(200);
            $swResponse->setHeader('mini-request-id', Seaslog::getRequestID(), true);
            $swResponse->end();
            return;
        }
        if (Context::has('IsInWebsocketEvent')) {
            ws_response()->push('');
        }
    }

    /**
     * @param Throwable $throwable
     */
    private function sendWebsocketException(Throwable $throwable): void
    {
        if (Context::has('IsInWebsocketEvent')) {
            $shouldClose = false;
            if ($throwable instanceof WebsocketException) {
                $code = $throwable->getCode();
                $content = [
                    'code' => $code,
                    'message' => $throwable->getMessage(),
                ];
                if (($code < 200 || $code > 300) && app(Translate::class)->has('http_status_code.' . $code)) {
                    $shouldClose = true;
                }
            } else {
                $content = $this->formatResponseException($throwable);
            }
            ws_response()->push(json_encode($content, JSON_UNESCAPED_UNICODE));
            if ($shouldClose) {
                ws_response()->close();
            }
        }
    }
}
