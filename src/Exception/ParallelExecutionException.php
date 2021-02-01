<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

/**
 * Class ParallelExecutionException
 * @package Mini\Exception
 */
class ParallelExecutionException extends \RuntimeException
{
    /**
     * @var array
     */
    private array $results = [];

    /**
     * @var array
     */
    private array $throwables = [];

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getThrowables(): array
    {
        return $this->throwables;
    }

    public function setThrowables(array $throwables): array
    {
        return $this->throwables = $throwables;
    }
}
