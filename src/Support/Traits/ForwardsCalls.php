<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

use BadMethodCallException;
use Error;

trait ForwardsCalls
{
    /**
     * Forward a method call to the given object.
     *
     * @param mixed $object
     * @param string $method
     * @param array $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    protected function forwardCallTo(mixed $object, string $method, array $parameters): mixed
    {
        try {
            return $object->{$method}(...$parameters);
        } catch (Error|BadMethodCallException $e) {
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';

            if (!preg_match($pattern, $e->getMessage(), $matches)) {
                throw $e;
            }

            if ($matches['method'] !== $method || $matches['class'] !== get_class($object)) {
                throw $e;
            }

            static::throwBadMethodCallException($method);
        }
    }

    /**
     * Forward a method call to the given object, returning $this if the forwarded call returned itself.
     *
     * @param mixed $object
     * @param string $method
     * @param array $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    protected function forwardDecoratedCallTo(mixed $object, string $method, array $parameters): mixed
    {
        $result = $this->forwardCallTo($object, $method, $parameters);

        return $result === $object ? $this : $result;
    }

    /**
     * Throw a bad method call exception for the given method.
     *
     * @param string $method
     * @return void
     *
     * @throws \BadMethodCallException
     */
    protected static function throwBadMethodCallException(string $method): void
    {
        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
