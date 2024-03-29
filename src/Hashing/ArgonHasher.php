<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Hashing;

use Mini\Contracts\Hasher as HasherContract;
use RuntimeException;

/**
 * Class ArgonHasher
 * @package Mini\Hashing
 */
class ArgonHasher extends AbstractHasher implements HasherContract
{
    /**
     * The default memory cost factor.
     *
     * @var int
     */
    protected int $memory = 1024;

    /**
     * The default time cost factor.
     *
     * @var int
     */
    protected int $time = 2;

    /**
     * The default threads factor.
     *
     * @var int
     */
    protected int $threads = 2;

    /**
     * Indicates whether to perform an algorithm check.
     *
     * @var bool
     */
    protected bool $verifyAlgorithm = false;

    /**
     * Create a new hasher instance.
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->time = $options['time'] ?? $this->time;
        $this->memory = $options['memory'] ?? $this->memory;
        $this->threads = $options['threads'] ?? $this->threads;
        $this->verifyAlgorithm = $options['verify'] ?? $this->verifyAlgorithm;
    }

    /**
     * Hash the given value.
     *
     * @param string $value
     * @param array $options
     * @return string
     *
     * @throws RuntimeException
     */
    public function make(string $value, array $options = []): string
    {
        $hash = @password_hash($value, $this->algorithm(), [
            'memory_cost' => $this->memory($options),
            'time_cost' => $this->time($options),
            'threads' => $this->threads($options),
        ]);

        if (!is_string($hash)) {
            throw new RuntimeException('Argon2 hashing not supported.');
        }

        return $hash;
    }

    /**
     * Get the algorithm that should be used for hashing.
     *
     * @return string
     */
    protected function algorithm(): string
    {
        return PASSWORD_ARGON2I;
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value
     * @param string $hashedValue
     * @param array $options
     * @return bool
     *
     * @throws RuntimeException
     */
    public function check(string $value, string $hashedValue = '', array $options = []): bool
    {
        if ($this->verifyAlgorithm && $this->info($hashedValue)['algoName'] !== 'argon2i') {
            throw new RuntimeException('This password does not use the Argon2i algorithm.');
        }

        return parent::check($value, $hashedValue, $options);
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
        return password_needs_rehash($hashedValue, $this->algorithm(), [
            'memory_cost' => $this->memory($options),
            'time_cost' => $this->time($options),
            'threads' => $this->threads($options),
        ]);
    }

    /**
     * Set the default password memory factor.
     *
     * @param int $memory
     * @return $this
     */
    public function setMemory(int $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Set the default password timing factor.
     *
     * @param int $time
     * @return $this
     */
    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Set the default password threads factor.
     *
     * @param int $threads
     * @return $this
     */
    public function setThreads(int $threads): self
    {
        $this->threads = $threads;

        return $this;
    }

    /**
     * Extract the memory cost value from the options array.
     *
     * @param array $options
     * @return int
     */
    protected function memory(array $options): int
    {
        return (int)($options['memory'] ?? $this->memory);
    }

    /**
     * Extract the time cost value from the options array.
     *
     * @param array $options
     * @return int
     */
    protected function time(array $options): int
    {
        return (int)($options['time'] ?? $this->time);
    }

    /**
     * Extract the threads value from the options array.
     *
     * @param array $options
     * @return int
     */
    protected function threads(array $options): int
    {
        return (int)($options['threads'] ?? $this->threads);
    }
}
