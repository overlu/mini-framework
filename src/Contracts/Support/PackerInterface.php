<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Support;

interface PackerInterface
{
    public function pack($data): string;

    public function unpack(string $data);
}
