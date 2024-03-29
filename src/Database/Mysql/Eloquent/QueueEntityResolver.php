<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

use Mini\Contracts\Queue\EntityNotFoundException;
use Mini\Contracts\Queue\EntityResolver as EntityResolverContract;

class QueueEntityResolver implements EntityResolverContract
{
    /**
     * Resolve the entity for the given ID.
     *
     * @param string $type
     * @param mixed $id
     * @return mixed
     *
     * @throws EntityNotFoundException
     */
    public function resolve(string $type, mixed $id): mixed
    {
        $instance = (new $type)->find($id);

        if ($instance) {
            return $instance;
        }

        throw new EntityNotFoundException($type, $id);
    }
}
