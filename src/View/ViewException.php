<?php

namespace Mini\View;

use ErrorException;
use Mini\Container\Container;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\Reflector;

class ViewException extends ErrorException
{
    /**
     * Report the exception.
     *
     * @return bool|null
     * @throws BindingResolutionException
     */
    public function report(): mixed
    {
        $exception = $this->getPrevious();

        if (Reflector::isCallable($reportCallable = [$exception, 'report'])) {
            return Container::getInstance()->call($reportCallable);
        }

        return false;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param \Mini\Http\Request $request
     * @return \Mini\Http\Response|null
     */
    public function render(\Mini\Http\Request $request)
    {
        $exception = $this->getPrevious();

        if ($exception && method_exists($exception, 'render')) {
            return $exception->render($request);
        }
    }
}
