<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exceptions;

use Mini\Context;
use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Logging\Log;
use Mini\Support\Command;
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

    public function __construct()
    {
        $this->environment = env('APP_ENV', 'production');
        $this->debug = env('APP_DEBUG', false);
    }

    /**
     * @throws InvalidResponseException
     * @throws Throwable
     */
    public function throw(Throwable $throwable): void
    {
        if ($throwable instanceof HttpResponseException) {
            write(failed($throwable->getMessage(), $throwable->getCode() ?? 0));
            return;
        }
        if ($this->environment !== 'production') {
            if (Context::has('IsInRequestEvent')) {
                $this->render(request(), $throwable);
            }
            $this->report($throwable);
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
}