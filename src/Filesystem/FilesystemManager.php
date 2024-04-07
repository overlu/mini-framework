<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

use Aws\S3\S3Client;
use Closure;
use Mini\Container\Container;
use Mini\Contracts\Storage as FactoryContract;
use Mini\Filesystem\OSS\Adapter as OSSAdapter;
use Mini\Filesystem\OSS\Plugins\Kernel;
use Mini\Filesystem\OSS\Plugins\SetBucket;
use Mini\Filesystem\OSS\Plugins\SignatureConfig;
use Mini\Filesystem\OSS\Plugins\Verify;
use Mini\Support\Arr;
use InvalidArgumentException;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as AwsS3PortableVisibilityConverter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\Ftp\FtpAdapter as FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PHPSecLibV2\SftpAdapter;
use League\Flysystem\PHPSecLibV2\SftpConnectionProvider;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

/**
 * Class FilesystemManager
 * @package Mini\Filesystem
 */
class FilesystemManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var Container
     */
    protected Container $app;

    /**
     * The array of resolved filesystem drivers.
     *
     * @var array
     */
    protected array $disks = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected array $customCreators = [];

    /**
     * Create a new filesystem manager instance.
     *
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Get a filesystem instance.
     *
     * @param string|null $name
     * @return \Mini\Contracts\Filesystem\Filesystem
     */
    public function drive(string $name = null): \Mini\Contracts\Filesystem\Filesystem
    {
        return $this->disk($name);
    }

    /**
     * Get a filesystem instance.
     *
     * @param string|null $name
     * @return \Mini\Contracts\Filesystem\Filesystem
     */
    public function disk(string $name = null): \Mini\Contracts\Filesystem\Filesystem
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->disks[$name] = $this->get($name);
    }

    /**
     * Get a default cloud filesystem instance.
     *
     * @return \Mini\Contracts\Filesystem\Filesystem
     */
    public function cloud(): \Mini\Contracts\Filesystem\Filesystem
    {
        $name = $this->getDefaultCloudDriver();

        return $this->disks[$name] = $this->get($name);
    }

    /**
     * Attempt to get the disk from the local cache.
     *
     * @param string $name
     * @return \Mini\Contracts\Filesystem\Filesystem
     */
    protected function get(string $name): \Mini\Contracts\Filesystem\Filesystem
    {
        return $this->disks[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given disk.
     *
     * @param string $name
     * @return \Mini\Contracts\Filesystem\Filesystem|null
     *
     */
    protected function resolve(string $name): ?\Mini\Contracts\Filesystem\Filesystem
    {
        $config = $this->getConfig($name);

        if (empty($config['driver'])) {
            throw new InvalidArgumentException("Disk [{$name}] does not have a configured driver.");
        }

        $name = $config['driver'];

        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create' . ucfirst($name) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$name}] is not supported.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param array $config
     * @return mixed
     */
    protected function callCustomCreator(array $config): mixed
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the local driver.
     *
     * @param array $config
     * @return FilesystemAdapter
     */
    public function createLocalDriver(array $config): FilesystemAdapter
    {
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? []
        );

        $links = ($config['links'] ?? null) === 'skip'
            ? LocalAdapter::SKIP_LINKS
            : LocalAdapter::DISALLOW_LINKS;

        $adapter = new LocalAdapter(
            $config['root'], $visibility, $config['lock'] ?? LOCK_EX, $links
        );

        return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
    }

    /**
     * Create an instance of the ftp driver.
     *
     * @param array $config
     * @return FilesystemAdapter
     */
    public function createFtpDriver(array $config): FilesystemAdapter
    {
        $adapter = new FtpAdapter(FtpConnectionOptions::fromArray($config));

        return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
    }

    /**
     * Create an instance of the sftp driver.
     *
     * @param array $config
     * @return FilesystemAdapter
     */
    public function createSftpDriver(array $config): FilesystemAdapter
    {
        $provider = SftpConnectionProvider::fromArray($config);

        $root = $config['root'] ?? '/';

        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? []
        );

        $adapter = new SftpAdapter($provider, $root, $visibility);

        return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
    }

    /**
     * Create an instance of the Amazon S3 driver.
     *
     * @param array $config
     * @return AwsS3V3Adapter
     */
    public function createS3Driver(array $config): AwsS3V3Adapter
    {
        $s3Config = $this->formatS3Config($config);

        $root = $s3Config['root'] ?? '';

        $visibility = new AwsS3PortableVisibilityConverter(
            $config['visibility'] ?? Visibility::PUBLIC
        );

        $streamReads = $s3Config['stream_reads'] ?? false;

        $client = new S3Client($s3Config);

        $adapter = new S3Adapter($client, $s3Config['bucket'], $root, $visibility, null, [], $streamReads);

        return new AwsS3V3Adapter(
            $this->createFlysystem($adapter, $config), $adapter, $s3Config, $client
        );
    }

    /**
     * @param $config
     * @return \Mini\Filesystem\OSSAdapter
     */
    public function createOssDriver($config): \Mini\Filesystem\OSSAdapter
    {
        $adapter = new OSSAdapter($config);

        $filesystem = new Flysystem($adapter, $config);

        return new \Mini\Filesystem\OSSAdapter($filesystem, $adapter, $config);
    }

    /**
     * Format the given S3 configuration with the default options.
     *
     * @param array $config
     * @return array
     */
    protected function formatS3Config(array $config): array
    {
        $config += ['version' => 'latest'];

        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return $config;
    }

    /**
     * Create a Flysystem instance with the given adapter.
     *
     * @param \League\Flysystem\FilesystemAdapter $adapter
     * @param array $config
     */
    protected function createFlysystem(FlysystemAdapter $adapter, array $config): Flysystem
    {
        $config = Arr::only($config, ['visibility', 'disable_asserts', 'url']);

        return new Flysystem($adapter, $config);
    }

    /**
     * Set the given disk instance.
     *
     * @param string $name
     * @param mixed $disk
     * @return $this
     */
    public function set(string $name, mixed $disk): self
    {
        $this->disks[$name] = $disk;

        return $this;
    }

    /**
     * Get the filesystem connection configuration.
     *
     * @param string $name
     * @return array
     */
    protected function getConfig(string $name): array
    {
        return config("filesystems.disks.{$name}", []);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return config('filesystems.default');
    }

    /**
     * Get the default cloud driver name.
     *
     * @return string
     */
    public function getDefaultCloudDriver(): string
    {
        return config('filesystems.cloud', 's3');
    }

    /**
     * Unset the given disk instances.
     *
     * @param array|string $disk
     * @return $this
     */
    public function forgetDisk(array|string $disk): self
    {
        foreach ((array)$disk as $diskName) {
            unset($this->disks[$diskName]);
        }

        return $this;
    }

    /**
     * Disconnect the given disk and remove from local cache.
     *
     * @param string|null $name
     * @return void
     */
    public function purge(string $name = null): void
    {
        $name = $name ?? $this->getDefaultDriver();

        unset($this->disks[$name]);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string $driver
     * @param \Closure $callback
     * @return $this
     */
    public function extend(string $driver, Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->disk()->$method(...$parameters);
    }
}
