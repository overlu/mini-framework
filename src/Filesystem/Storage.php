<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

/**
 * Class Storage
 * @package Mini\Filesystem
 * @method static \Mini\Contracts\Filesystem\Filesystem assertExists(string|array $path)
 * @method static \Mini\Contracts\Filesystem\Filesystem assertMissing(string|array $path)
 * @method static \Mini\Contracts\Filesystem\Filesystem cloud()
 * @method static \Mini\Contracts\Filesystem\Filesystem disk(string $name = null)
 * @method static \Mini\Contracts\Filesystem\Filesystem createLocalDriver(array $config)
 * @method static \Mini\Contracts\Filesystem\Filesystem createFtpDriver(array $config)
 * @method static \Mini\Contracts\Filesystem\Filesystem createSftpDriver(array $config)
 * @method static \Mini\Contracts\Filesystem\Filesystem createS3Driver(array $config)
 * @method static \Mini\Filesystem\FilesystemManager extend(string $driver, \Closure $callback)
 * @method static array allDirectories(string|null $directory = null)
 * @method static array allFiles(string|null $directory = null)
 * @method static array directories(string|null $directory = null, bool $recursive = false)
 * @method static array files(string|null $directory = null, bool $recursive = false)
 * @method static bool append(string $path, string $data)
 * @method static bool copy(string $from, string $to)
 * @method static bool delete(string|array $paths)
 * @method static bool deleteDirectory(string $directory)
 * @method static bool exists(string $path)
 * @method static bool makeDirectory(string $path)
 * @method static bool move(string $from, string $to)
 * @method static bool prepend(string $path, string $data)
 * @method static bool put(string $path, string|resource $contents, mixed $options = [])
 * @method static string|false putFile(string $path, \Mini\Http\File|\Mini\Http\UploadedFile|string $file, mixed $options = [])
 * @method static string|false putFileAs(string $path, \Mini\Http\File|\Mini\Http\UploadedFile|string $file, string $name, mixed $options = [])
 * @method static bool setVisibility(string $path, string $visibility)
 * @method static bool writeStream(string $path, resource $resource, array $options = [])
 * @method static int lastModified(string $path)
 * @method static int size(string $path)
 * @method static resource|null readStream(string $path)
 * @method static string get(string $path)
 * @method static string getVisibility(string $path)
 * @method static string temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = [])
 * @method static string url(string $path)
 *
 * @see \Mini\Filesystem\FilesystemManager
 */
class Storage
{
    /**
     * Replace the given disk with a local testing disk.
     *
     * @param string|null $disk
     * @param array $config
     * @return \Mini\Contracts\Filesystem\Filesystem
     */
    public static function fake($disk = null, array $config = [])
    {
        $disk = $disk ?: config('filesystems.default');

        (new Filesystem)->cleanDirectory(
            $root = storage_path('testing/disks/' . $disk)
        );

        static::set($disk, $fake = static::createLocalDriver(array_merge($config, [
            'root' => $root,
        ])));

        return $fake;
    }

    /**
     * Replace the given disk with a persistent local testing disk.
     *
     * @param string|null $disk
     * @param array $config
     * @return \Mini\Contracts\Filesystem\Filesystem
     */
    public static function persistentFake($disk = null, array $config = [])
    {
        $disk = $disk ?: config('filesystems.default');

        static::set($disk, $fake = static::createLocalDriver(array_merge($config, [
            'root' => storage_path('testing/disks/' . $disk),
        ])));

        return $fake;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return app('filesystem')->{$name}(...$arguments);
    }
}