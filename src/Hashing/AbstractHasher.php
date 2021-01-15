<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Hashing;

/**
 * Class AbstractHasher
 * @package Mini\Hashing
 */
abstract class AbstractHasher
{
    /**
     * Get information about the given hashed value.
     *
     * @param string $hashedValue
     * @return array
     */
    public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
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
        if ($hashedValue === '') {
            return false;
        }
        return password_verify($value, $hashedValue);
    }

    public function verify(string $value, string $hashedValue = '', array $options = []): bool
    {
        return $this->check($value, $hashedValue, $options);
    }
}
