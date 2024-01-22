<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Hashing;

use Mini\Contracts\Hasher;
use Mini\Support\Manager;

/**
 * Class HashManager
 * @package Mini\Hashing
 */
class HashManager extends Manager implements Hasher
{
    /**
     * Create an instance of the Bcrypt hash Driver.
     *
     * @return BcryptHasher
     */
    public function createBcryptDriver(): BcryptHasher
    {
        return new BcryptHasher(config('hashing.bcrypt', []));
    }

    /**
     * Create an instance of the Argon2i hash Driver.
     *
     * @return ArgonHasher
     */
    public function createArgonDriver(): ArgonHasher
    {
        return new ArgonHasher(config('hashing.argon', []));
    }

    /**
     * Create an instance of the Argon2id hash Driver.
     *
     * @return Argon2IdHasher
     */
    public function createArgon2idDriver(): Argon2IdHasher
    {
        return new Argon2IdHasher(config('hashing.argon', []));
    }

    /**
     * Get information about the given hashed value.
     *
     * @param string $hashedValue
     * @return array
     */
    public function info(string $hashedValue): array
    {
        return $this->driver()->info($hashedValue);
    }

    /**
     * Hash the given value.
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    public function make(string $value, array $options = []): string
    {
        return $this->driver()->make($value, $options);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value
     * @param string $hashedValue
     * @param array $options
     * @return bool
     */
    public function check(string $value, string $hashedValue = '', array $options = []): bool
    {
        return $this->driver()->check($value, $hashedValue, $options);
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue
     * @param array $options
     * @return bool
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return $this->driver()->needsRehash($hashedValue, $options);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return config('hashing.driver', 'bcrypt');
    }
}
