<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Packer;

use Mini\Contracts\Support\PackerInterface;

class PhpSerializerPacker implements PackerInterface
{
    public function pack($data): string
    {
        return serialize($data);
    }

    public function unpack(string $data)
    {
        return unserialize($data, ["allowed_classes" => true]);
    }
}
