<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Filesystem;

/**
 * @method \Mini\Contracts\Filesystem\Filesystem assertExists(string|array $path)
 * @method \Mini\Contracts\Filesystem\Filesystem assertMissing(string|array $path)
 * @method \Mini\Contracts\Filesystem\Filesystem cloud()
 * @method array allDirectories(string|null $directory = null)
 * @method array allFiles(string|null $directory = null)
 * @method array directories(string|null $directory = null, bool $recursive = false)
 * @method array files(string|null $directory = null, bool $recursive = false)
 * @method bool append(string $path, string $data)
 * @method bool copy(string $from, string $to)
 * @method bool delete(string|array $paths)
 * @method bool deleteDirectory(string $directory)
 * @method bool exists(string $path)
 * @method \Mini\Filesystem\FilesystemManager extend(string $driver, \Closure $callback)
 * @method bool makeDirectory(string $path)
 * @method bool move(string $from, string $to)
 * @method bool prepend(string $path, string $data)
 * @method bool put(string $path, string|resource $contents, mixed $options = [])
 * @method string|false putFile(string $path, \Mini\Http\File|\Mini\Http\UploadedFile|string $file, mixed $options = [])
 * @method string|false putFileAs(string $path, \Mini\Http\File|\Mini\Http\UploadedFile|string $file, string $name, mixed $options = [])
 * @method bool setVisibility(string $path, string $visibility)
 * @method bool writeStream(string $path, resource $resource, array $options = [])
 * @method int lastModified(string $path)
 * @method int size(string $path)
 * @method resource|null readStream(string $path)
 * @method string get(string $name)
 * @method string getVisibility(string $path)
 * @method string temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = [])
 * @method string url(string $path)
 * @method array ossCallbackVerify()
 * @method array getOssSignatureConfig(string $prefix = '', ?string $callBackUrl = null, array $customData = [], int $expire = 30, int $contentLengthRangeValue = 1048576000, array $systemData = [])
 *
 * @see \Mini\Filesystem\FilesystemManager
 */
interface Factory
{
    /**
     * Get a filesystem implementation.
     *
     * @param string|null $name
     * @return Filesystem
     */
    public function disk(string $name = null): Filesystem;
}
