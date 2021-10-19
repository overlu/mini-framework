<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Encryption;

use Mini\Exception\DecryptException;
use Mini\Exception\EncryptException;

interface StringEncrypter
{
    /**
     * Encrypt a string without serialization.
     *
     * @param string $value
     * @return string
     *
     * @throws EncryptException
     */
    public function encryptString(string $value): string;

    /**
     * Decrypt the given string without unserialization.
     *
     * @param string $payload
     * @return string
     *
     * @throws DecryptException
     */
    public function decryptString(string $payload): string;
}
