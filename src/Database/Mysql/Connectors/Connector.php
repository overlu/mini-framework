<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Connectors;

use Doctrine\DBAL\Driver\PDOConnection;
use Exception;
use Mini\Database\Mysql\DetectsLostConnections;
use PDO;
use Throwable;

class Connector
{
    use DetectsLostConnections;

    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Create a new PDO connection.
     *
     * @param string $dsn
     * @param array $config
     * @param array $options
     * @return PDO
     */
    public function createConnection(string $dsn, array $config, array $options): PDO|PDOConnection
    {
        [$username, $password] = [
            $config['username'] ?? null, $config['password'] ?? null,
        ];

        try {
            return $this->createPdoConnection(
                $dsn, $username, $password, $options
            );
        } catch (Exception $e) {
            return $this->tryAgainIfCausedByLostConnection(
                $e, $dsn, $username, $password, $options
            );
        }
    }

    /**
     * Create a new PDO connection instance.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @return PDO
     */
    protected function createPdoConnection(string $dsn, string $username, string $password, array $options): PDO|PDOConnection
    {
        if (class_exists(PDOConnection::class) && !$this->isPersistentConnection($options)) {
            return new PDOConnection($dsn, $username, $password, $options);
        }

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Determine if the connection is persistent.
     *
     * @param array $options
     * @return bool
     */
    protected function isPersistentConnection(array $options): bool
    {
        return isset($options[PDO::ATTR_PERSISTENT]) &&
            $options[PDO::ATTR_PERSISTENT];
    }

    /**
     * Handle an exception that occurred during connect execution.
     *
     * @param Throwable $e
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @return PDO|PDOConnection
     *
     * @throws Throwable
     */
    protected function tryAgainIfCausedByLostConnection(Throwable $e, string $dsn, string $username, string $password, array $options): PDO|PDOConnection
    {
        if ($this->causedByLostConnection($e)) {
            return $this->createPdoConnection($dsn, $username, $password, $options);
        }

        throw $e;
    }

    /**
     * Get the PDO options based on the configuration.
     *
     * @param array $config
     * @return array
     */
    public function getOptions(array $config): array
    {
        $options = $config['options'] ?? [];

        return array_diff_key($this->options, $options) + $options;
    }

    /**
     * Get the default PDO connection options.
     *
     * @return array
     */
    public function getDefaultOptions(): array
    {
        return $this->options;
    }

    /**
     * Set the default PDO connection options.
     *
     * @param array $options
     * @return void
     */
    public function setDefaultOptions(array $options): void
    {
        $this->options = $options;
    }
}
