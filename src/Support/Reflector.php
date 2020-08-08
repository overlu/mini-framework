<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

class Reflector
{
    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * @param ReflectionParameter $parameter
     * @return string|null
     */
    public static function getParameterClassName($parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if ($name === 'self') {
            return $parameter->getDeclaringClass()->getName();
        }

        return $name;
    }

    /**
     * Determine if the parameter's type is a subclass of the given type.
     *
     * @param ReflectionParameter $parameter
     * @param string $className
     * @return bool
     * @throws ReflectionException
     */
    public static function isParameterSubclassOf($parameter, $className): bool
    {
        $paramClassName = static::getParameterClassName($parameter);

        return ($paramClassName && class_exists($paramClassName))
            ? (new ReflectionClass($paramClassName))->isSubclassOf($className)
            : false;
    }
}
