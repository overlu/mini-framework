<<<<<<< HEAD
<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Queue;

interface EntityResolver
{
    /**
     * Resolve the entity for the given ID.
     *
     * @param string $type
     * @param mixed $id
     * @return mixed
     */
    public function resolve($type, $id);
}
=======
<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Queue;

interface EntityResolver
{
    /**
     * Resolve the entity for the given ID.
     *
     * @param string $type
     * @param mixed $id
     * @return mixed
     */
    public function resolve(string $type, $id);
}
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
