<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session\Drivers;

use SessionHandlerInterface;

class NullSessionHandler implements SessionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $id): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $id, string $data): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $id): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc(int $max_lifetime): bool
    {
        return true;
    }
}
