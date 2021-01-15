<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Connectors;

use Exception;
use PDO;
use Throwable;

class MySqlConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @param array $config
<<<<<<< HEAD
     * @return \PDO
=======
     * @return PDO
     * @throws Exception
     * @throws Throwable
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function connect(array $config): PDO
    {
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
        $connection = $this->createConnection($dsn, $config, $options);

        if (!empty($config['database'])) {
            $connection->exec("use `{$config['database']}`;");
        }

        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        $this->configureTimezone($connection, $config);

        $this->setModes($connection, $config);

        return $connection;
    }

    /**
     * Set the connection character set and collation.
     *
<<<<<<< HEAD
     * @param \PDO $connection
=======
     * @param PDO $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $config
     * @return void
     */
    protected function configureEncoding(PDO $connection, array $config): void
    {
        if (!isset($config['charset'])) {
<<<<<<< HEAD
            return $connection;
=======
            return;
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
        }

        $connection->prepare(
            "set names '{$config['charset']}'" . $this->getCollation($config)
        )->execute();
    }

    /**
     * Get the collation for the configuration.
     *
     * @param array $config
     * @return string
     */
    protected function getCollation(array $config): string
    {
        return isset($config['collation']) ? " collate '{$config['collation']}'" : '';
    }

    /**
     * Set the timezone on the connection.
     *
<<<<<<< HEAD
     * @param \PDO $connection
=======
     * @param PDO $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $config
     * @return void
     */
    protected function configureTimezone(PDO $connection, array $config): void
    {
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone="' . $config['timezone'] . '"')->execute();
        }
    }

    /**
     * Create a DSN string from a configuration.
     *
     * Chooses socket or host/port based on the 'unix_socket' config value.
     *
     * @param array $config
     * @return string
     */
    protected function getDsn(array $config): string
    {
        return $this->hasSocket($config)
            ? $this->getSocketDsn($config)
            : $this->getHostDsn($config);
    }

    /**
     * Determine if the given configuration array has a UNIX socket value.
     *
     * @param array $config
     * @return bool
     */
    protected function hasSocket(array $config): bool
    {
        return isset($config['unix_socket']) && !empty($config['unix_socket']);
    }

    /**
     * Get the DSN string for a socket configuration.
     *
     * @param array $config
     * @return string
     */
    protected function getSocketDsn(array $config): string
    {
        return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
    }

    /**
     * Get the DSN string for a host / port configuration.
     *
     * @param array $config
     * @return string
     */
    protected function getHostDsn(array $config): string
    {
        extract($config, EXTR_SKIP);

        return isset($port)
            ? "mysql:host={$host};port={$port};dbname={$database}"
            : "mysql:host={$host};dbname={$database}";
    }

    /**
     * Set the modes for the connection.
     *
<<<<<<< HEAD
     * @param \PDO $connection
=======
     * @param PDO $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $config
     * @return void
     */
    protected function setModes(PDO $connection, array $config): void
    {
        if (isset($config['modes'])) {
            $this->setCustomModes($connection, $config);
        } elseif (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->prepare($this->strictMode($connection, $config))->execute();
            } else {
                $connection->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
            }
        }
    }

    /**
     * Set the custom modes on the connection.
     *
<<<<<<< HEAD
     * @param \PDO $connection
=======
     * @param PDO $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $config
     * @return void
     */
    protected function setCustomModes(PDO $connection, array $config): void
    {
        $modes = implode(',', $config['modes']);

        $connection->prepare("set session sql_mode='{$modes}'")->execute();
    }

    /**
     * Get the query to enable strict mode.
     *
<<<<<<< HEAD
     * @param \PDO $connection
=======
     * @param PDO $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $config
     * @return string
     */
    protected function strictMode(PDO $connection, array $config): string
    {
        $version = $config['version'] ?? $connection->getAttribute(PDO::ATTR_SERVER_VERSION);

        if (version_compare($version, '8.0.11') >= 0) {
            return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
        }

        return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'";
    }
}
