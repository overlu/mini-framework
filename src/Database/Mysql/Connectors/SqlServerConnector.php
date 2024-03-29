<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Connectors;

use Mini\Support\Arr;
use PDO;

class SqlServerConnector extends Connector implements ConnectorInterface
{
    /**
     * The PDO connection options.
     *
     * @var array
     */
    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    /**
     * Establish a database connection.
     *
     * @param array $config
     * @return PDO
     */
    public function connect(array $config): PDO
    {
        $options = $this->getOptions($config);

        return $this->createConnection($this->getDsn($config), $config, $options);
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param array $config
     * @return string
     */
    protected function getDsn(array $config): ?string
    {
        // First we will create the basic DSN setup as well as the port if it is in
        // in the configuration options. This will give us the basic DSN we will
        // need to establish the PDO connections and return them back for use.
        if ($this->prefersOdbc($config)) {
            return $this->getOdbcDsn($config);
        }

        if (in_array('sqlsrv', $this->getAvailableDrivers(), true)) {
            return $this->getSqlSrvDsn($config);
        }

        return $this->getDblibDsn($config);
    }

    /**
     * Determine if the database configuration prefers ODBC.
     *
     * @param array $config
     * @return bool
     */
    protected function prefersOdbc(array $config): bool
    {
        return in_array('odbc', $this->getAvailableDrivers(), true) &&
            ($config['odbc'] ?? null) === true;
    }

    /**
     * Get the DSN string for a DbLib connection.
     *
     * @param array $config
     * @return string
     */
    protected function getDblibDsn(array $config): string
    {
        return $this->buildConnectString('dblib', array_merge([
            'host' => $this->buildHostString($config, ':'),
            'dbname' => $config['database'],
        ], Arr::only($config, ['appname', 'charset', 'version'])));
    }

    /**
     * Get the DSN string for an ODBC connection.
     *
     * @param array $config
     * @return string
     */
    protected function getOdbcDsn(array $config): string
    {
        return isset($config['odbc_datasource_name'])
            ? 'odbc:' . $config['odbc_datasource_name'] : '';
    }

    /**
     * Get the DSN string for a SqlSrv connection.
     *
     * @param array $config
     * @return string
     */
    protected function getSqlSrvDsn(array $config): string
    {
        $arguments = [
            'Server' => $this->buildHostString($config, ','),
        ];

        if (isset($config['database'])) {
            $arguments['Database'] = $config['database'];
        }

        if (isset($config['readonly'])) {
            $arguments['ApplicationIntent'] = 'ReadOnly';
        }

        if (isset($config['pooling']) && $config['pooling'] === false) {
            $arguments['ConnectionPooling'] = '0';
        }

        if (isset($config['appname'])) {
            $arguments['APP'] = $config['appname'];
        }

        if (isset($config['encrypt'])) {
            $arguments['Encrypt'] = $config['encrypt'];
        }

        if (isset($config['trust_server_certificate'])) {
            $arguments['TrustServerCertificate'] = $config['trust_server_certificate'];
        }

        if (isset($config['multiple_active_result_sets']) && $config['multiple_active_result_sets'] === false) {
            $arguments['MultipleActiveResultSets'] = 'false';
        }

        if (isset($config['transaction_isolation'])) {
            $arguments['TransactionIsolation'] = $config['transaction_isolation'];
        }

        if (isset($config['multi_subnet_failover'])) {
            $arguments['MultiSubnetFailover'] = $config['multi_subnet_failover'];
        }

        if (isset($config['column_encryption'])) {
            $arguments['ColumnEncryption'] = $config['column_encryption'];
        }

        if (isset($config['key_store_authentication'])) {
            $arguments['KeyStoreAuthentication'] = $config['key_store_authentication'];
        }

        if (isset($config['key_store_principal_id'])) {
            $arguments['KeyStorePrincipalId'] = $config['key_store_principal_id'];
        }

        if (isset($config['key_store_secret'])) {
            $arguments['KeyStoreSecret'] = $config['key_store_secret'];
        }

        return $this->buildConnectString('sqlsrv', $arguments);
    }

    /**
     * Build a connection string from the given arguments.
     *
     * @param string $driver
     * @param array $arguments
     * @return string
     */
    protected function buildConnectString(string $driver, array $arguments): string
    {
        return $driver . ':' . implode(';', array_map(function ($key) use ($arguments) {
                return sprintf('%s=%s', $key, $arguments[$key]);
            }, array_keys($arguments)));
    }

    /**
     * Build a host string from the given configuration.
     *
     * @param array $config
     * @param string $separator
     * @return string
     */
    protected function buildHostString(array $config, string $separator): string
    {
        if (empty($config['port'])) {
            return $config['host'];
        }

        return $config['host'] . $separator . $config['port'];
    }

    /**
     * Get the available PDO drivers.
     *
     * @return array
     */
    protected function getAvailableDrivers(): array
    {
        return PDO::getAvailableDrivers();
    }
}
