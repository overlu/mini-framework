<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Encryption;

use Mini\Exception\DecryptException;
use Mini\Exception\EncryptException;

interface Encrypter
{
    /**
     * Encrypt the given value.
     *
     * @param mixed $value
     * @param bool $serialize
     * @return string
     *
     * @throws EncryptException
     */
    public function encrypt(mixed $value, bool $serialize = true): string;

    /**
     * Decrypt the given value.
     *
     * @param string $payload
     * @param bool $unserialize
     * @return mixed
     *
     * @throws DecryptException
     */
    public function decrypt(string $payload, bool $unserialize = true): mixed;
}
