<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Filesystem\Filesystem;

/**
 * @method static \Mini\Contracts\Filesystem\Filesystem assertExists(string|array $path)
 * @method static \Mini\Contracts\Filesystem\Filesystem assertMissing(string|array $path)
 * @method static \Mini\Contracts\Filesystem\Filesystem cloud()
 * @method static \Mini\Contracts\Filesystem\Filesystem disk(string $name = null)
 * @method static array allDirectories(string|null $directory = null)
 * @method static array allFiles(string|null $directory = null)
 * @method static array directories(string|null $directory = null, bool $recursive = false)
 * @method static array files(string|null $directory = null, bool $recursive = false)
 * @method static bool append(string $path, string $data)
 * @method static bool copy(string $from, string $to)
 * @method static bool delete(string|array $paths)
 * @method static bool deleteDirectory(string $directory)
 * @method static bool exists(string $path)
 * @method static \Mini\Filesystem\FilesystemManager extend(string $driver, \Closure $callback)
 * @method static bool makeDirectory(string $path)
 * @method static bool move(string $from, string $to)
 * @method static bool prepend(string $path, string $data)
 * @method static bool put(string $path, string|resource $contents, mixed $options = [])
 * @method static string|false putFile(string $path, \Mini\Filesystem\File|\Mini\Service\HttpMessage\Upload\UploadedFile|string $file, mixed $options = [])
 * @method static string|false putFileAs(string $path, \Mini\Filesystem\File|\Mini\Service\HttpMessage\Upload\UploadedFile|string $file, string $name, mixed $options = [])
 * @method static bool setVisibility(string $path, string $visibility)
 * @method static bool writeStream(string $path, resource $resource, array $options = [])
 * @method static int lastModified(string $path)
 * @method static int size(string $path)
 * @method static resource|null readStream(string $path)
 * @method static string get(string $path)
 * @method static string getVisibility(string $path)
 * @method static string temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = [])
 * @method static array temporaryUploadUrl(string $path, \DateTimeInterface $expiration, array $options = [])
 * @method static string url(string $path)
 * @method static array ossCallbackVerify()
 * @method static array getOssSignatureConfig(string $prefix = '', ?string $callBackUrl = null, array $customData = [], int $expire = 30, int $contentLengthRangeValue = 1048576000, array $systemData = [])
 *
 * @see \Mini\Filesystem\FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'filesystem';
    }
}
