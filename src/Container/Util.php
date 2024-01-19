<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Container;

use Closure;
use ReflectionNamedType;

class Util
{
    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * From Arr::wrap() in Mini\Support.
     *
     * @param  mixed  $value
     * @return array
     */
    public static function arrayWrap(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Return the default value of the given value.
     *
     * From global value() helper in Mini\Support.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function unwrapIfClosure(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * * Get the class name of the given parameter's type, if possible.
     *
     * From Reflector::getParameterClassName() in Mini\Support.
     *
     * @param $parameter
     * @return string|void
     */
    public static function getParameterClassName($parameter)
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return;
        }

        $name = $type->getName();

        if ($name === 'self') {
            return $parameter->getDeclaringClass()->getName();
        }

        return $name;
    }
}
