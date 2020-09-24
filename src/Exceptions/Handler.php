<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exceptions;

use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Logging\Log;
use Mini\Support\Command;
use Swoole\ExitException;
use Throwable;

class Handler implements HandlerInterface
{
    protected bool $debug = false;

    protected string $environment = 'production';

    protected array $dontReport = [];

    protected Throwable $throwable;

    public function __construct(Throwable $throwable)
    {
        $this->environment = env('APP_ENV', 'production');
        $this->debug = env('APP_DEBUG', false);
        $this->throwable = $throwable;
    }

    /**
     * @throws InvalidResponseException
     * @throws Throwable
     */
    public function throw(): void
    {
        if ($this->throwable instanceof HttpResponseException) {
            write(failed($this->throwable->getMessage(), $this->throwable->getCode() ?? 0));
            return;
        }
        if ($this->environment !== 'production') {
            $request = request();
            if (class_exists(\App\Exceptions\Handler::class)) {
                $handler = new \App\Exceptions\Handler($this->throwable);
                if ($handler instanceof HandlerInterface) {
                    if ($response = response()) {
                        $handler->render($request, $this->throwable);
                    }
                    $handler->report($this->throwable);
                }
            } else {
                if ($response = response()) {
                    $this->render($request, $this->throwable);
                }
                $this->report($this->throwable);
            }
        } else {
            abort(500, 'The server is busy, please try again later.');
        }
    }

    /**
     * @param Throwable $throwable
     * @throws Throwable
     */
    public function report(Throwable $throwable): void
    {
        if ($this->checkDontReport() && !$throwable instanceof ExitException) {
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
        if ($this->checkDontReport()) {
            abort(500, $this->formatResponseException($throwable));
            Log::error($this->format($this->throwable));
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
     * @return bool
     */
    private function checkDontReport(): bool
    {
        foreach ($this->dontReport as $throw) {
            if ($this->throwable instanceof $throw) {
                return false;
            }
        }
        return true;
    }
}