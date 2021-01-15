<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

use Mini\Http\Resources\Json\JsonResource;
use RuntimeException;

class JsonEncodingException extends RuntimeException
{
    /**
     * Create a new JSON encoding exception for the model.
     *
     * @param mixed $model
     * @param string $message
     * @return static
     */
    public static function forModel($model, string $message)
    {
        return new static('Error encoding model [' . get_class($model) . '] with ID [' . $model->getKey() . '] to JSON: ' . $message);
    }

    /**
     * Create a new JSON encoding exception for the resource.
     *
<<<<<<< HEAD
     * @param \Mini\Http\Resources\Json\JsonResource $resource
=======
     * @param JsonResource $resource
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $message
     * @return static
     */
    public static function forResource($resource, string $message)
    {
        $model = $resource->resource;

        return new static('Error encoding resource [' . get_class($resource) . '] with model [' . get_class($model) . '] with ID [' . $model->getKey() . '] to JSON: ' . $message);
    }

    /**
     * Create a new JSON encoding exception for an attribute.
     *
     * @param mixed $model
     * @param mixed $key
     * @param string $message
     * @return static
     */
    public static function forAttribute($model, $key, string $message)
    {
        $class = get_class($model);

        return new static("Unable to encode attribute [{$key}] for model [{$class}] to JSON: {$message}.");
    }
}
