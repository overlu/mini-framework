<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

use DateTimeInterface;
use Mini\Contracts\Filesystem\Cloud as CloudFilesystemContract;
use Mini\Contracts\Filesystem\Filesystem as FilesystemContract;
use Mini\Exception\FileNotFoundException;
use Mini\Http\UploadedFile;
use Mini\Support\Arr;
use Mini\Support\Str;
use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class FilesystemAdapter
 * @package Mini\Filesystem
 */
class FilesystemAdapter implements CloudFilesystemContract
{
    /**
     * The Flysystem filesystem implementation.
     *
     * @var FilesystemOperator
     */
    protected FilesystemOperator $driver;

    /**
     * The Flysystem adapter implementation.
     *
     * @var FlysystemAdapter
     */
    protected FlysystemAdapter $adapter;

    /**
     * The filesystem configuration.
     *
     * @var array
     */
    protected array $config;

    /**
     * The Flysystem PathPrefixer instance.
     *
     * @var PathPrefixer
     */
    protected PathPrefixer $prefixer;

    /**
     * Create a new filesystem adapter instance.
     *
     * @param FilesystemOperator $driver
     * @param FlysystemAdapter $adapter
     * @param array $config
     * @return void
     */
    public function __construct(FilesystemOperator $driver, FlysystemAdapter $adapter, array $config = [])
    {
        $this->driver = $driver;
        $this->adapter = $adapter;
        $this->config = $config;
        $this->prefixer = new PathPrefixer(
            $config['root'] ?? '', $config['directory_separator'] ?? DIRECTORY_SEPARATOR
        );
    }

    /**
     * Assert that the given file exists.
     *
     * @param array|string $path
     * @param string|null $content
     * @return $this
     */
    public function assertExists(array|string $path, string $content = null): self
    {
        $paths = Arr::wrap($path);

        foreach ($paths as $ph) {
            PHPUnit::assertTrue(
                $this->exists($ph), "Unable to find a file at path [{$ph}]."
            );

            if (!is_null($content)) {
                $actual = $this->get($ph);

                PHPUnit::assertSame(
                    $content,
                    $actual,
                    "File [{$ph}] was found, but content [{$actual}] does not match [{$content}]."
                );
            }
        }

        return $this;
    }

    /**
     * Assert that the given file does not exist.
     *
     * @param array|string $path
     * @return $this
     */
    public function assertMissing(array|string $path): self
    {
        $paths = Arr::wrap($path);

        foreach ($paths as $ph) {
            PHPUnit::assertFalse(
                $this->exists($ph), "Found unexpected file at path [{$ph}]."
            );
        }

        return $this;
    }

    /**
     * Determine if a file exists.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return $this->driver->fileExists($path);
    }

    /**
     * Determine if a file or directory is missing.
     *
     * @param string $path
     * @return bool
     */
    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    /**
     * Get the full path for the file at the given "short" path.
     *
     * @param string $path
     * @return string
     */
    public function path(string $path): string
    {
        return $this->prefixer->prefixPath($path);
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @return string
     */
    public function get(string $path): string
    {
        try {
            return $this->driver->read($path);
        } catch (UnableToReadFile $e) {
            return '';
        }
    }

    /**
     * Create a streamed response for a given file.
     *
     * @param string $path
     * @param string|null $name
     * @param array $headers
     * @param string|null $disposition
     * @return StreamedResponse
     * @throws FileNotFoundException
     */
    public function response(string $path, string $name = null, array $headers = [], ?string $disposition = 'inline'): StreamedResponse
    {
        $response = new StreamedResponse;

        $filename = $name ?? basename($path);

        $disposition = $response->headers->makeDisposition(
            $disposition, $filename, $this->fallbackName($filename)
        );

        $response->headers->replace($headers + [
                'Content-Type' => $this->mimeType($path),
                'Content-Length' => $this->size($path),
                'Content-Disposition' => $disposition,
            ]);

        $response->setCallback(function () use ($path) {
            $stream = $this->readStream($path);
            fpassthru($stream);
            fclose($stream);
        });

        return $response;
    }

    /**
     * Create a streamed download response for a given file.
     *
     * @param string $path
     * @param string|null $name
     * @param array $headers
     * @return StreamedResponse
     * @throws FileNotFoundException
     */
    public function download(string $path, string $name = null, array $headers = []): StreamedResponse
    {
        return $this->response($path, $name, $headers, 'attachment');
    }

    /**
     * Convert the string to ASCII characters that are equivalent to the given name.
     *
     * @param string $name
     * @return string
     */
    protected function fallbackName(string $name): string
    {
        return str_replace('%', '', Str::ascii($name));
    }

    /**
     * Write the contents of a file.
     *
     * @param string $path
     * @param string|resource $contents
     * @param mixed|array $options
     * @return bool
     */
    public function put(string $path, $contents, mixed $options = []): bool
    {
        $options = is_string($options)
            ? ['visibility' => $options]
            : (array)$options;

        // If the given contents is actually a file or uploaded file instance than we will
        // automatically store the file using a stream. This provides a convenient path
        // for the developer to store streams without managing them manually in code.
        if ($contents instanceof File ||
            $contents instanceof UploadedFile) {
            return $this->putFile($path, $contents, $options);
        }

        try {
            if ($contents instanceof StreamInterface) {
                $this->driver->writeStream($path, $contents->detach(), $options);

                return true;
            }

            is_resource($contents)
                ? $this->driver->writeStream($path, $contents, $options)
                : $this->driver->write($path, $contents, $options);
        } catch (UnableToWriteFile $e) {
            return false;
        }

        return true;
    }

    /**
     * Store the uploaded file on the disk.
     *
     * @param string $path
     * @param \Mini\Http\UploadedFile|string|File $file
     * @param mixed|array $options
     * @return string|false
     */
    public function putFile(string $path, UploadedFile|File|string $file, mixed $options = []): bool|string
    {
        $file = is_string($file) ? new File($file) : $file;

        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    /**
     * Store the uploaded file on the disk with a given name.
     *
     * @param string $path
     * @param \Mini\Http\UploadedFile|\Mini\Http\File|string $file
     * @param string $name
     * @param mixed $options
     * @return string|false
     */
    public function putFileAs(string $path, UploadedFile|\Mini\Http\File|string $file, string $name, mixed $options = []): bool|string
    {
        $stream = fopen(is_string($file) ? $file : $file->getRealPath(), 'r');

        // Next, we will format the path of the file and store the file using a stream since
        // they provide better performance than alternatives. Once we write the file this
        // stream will get closed automatically by us so the developer doesn't have to.
        $result = $this->put(
            $path = trim($path . '/' . $name, '/'), $stream, $options
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }

    /**
     * Get the visibility for the given path.
     *
     * @param string $path
     * @return string
     */
    public function getVisibility(string $path): string
    {
        if ($this->driver->visibility($path) === Visibility::PUBLIC) {
            return FilesystemContract::VISIBILITY_PUBLIC;
        }

        return FilesystemContract::VISIBILITY_PRIVATE;
    }

    /**
     * Set the visibility for the given path.
     *
     * @param string $path
     * @param string $visibility
     * @return bool
     */
    public function setVisibility(string $path, string $visibility): bool
    {
        try {
            $this->driver->setVisibility($path, $this->parseVisibility($visibility));
        } catch (UnableToSetVisibility $e) {
            return false;
        }

        return true;
    }

    /**
     * Prepend to a file.
     *
     * @param string $path
     * @param string $data
     * @param string $separator
     * @return bool
     */
    public function prepend(string $path, string $data, string $separator = PHP_EOL): bool
    {
        if ($this->exists($path)) {
            return $this->put($path, $data . $separator . $this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file.
     *
     * @param string $path
     * @param string $data
     * @return bool
     */
    public function append(string $path, string $data, string $separator = PHP_EOL): bool
    {
        if ($this->exists($path)) {
            return $this->put($path, $this->get($path) . $separator . $data);
        }

        return $this->put($path, $data);
    }

    /**
     * Delete the file at a given path.
     *
     * @param array|string $paths
     * @return bool
     */
    public function delete(array|string $paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                $this->driver->delete($path);
            } catch (UnableToDeleteFile $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Copy a file to a new location.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function copy(string $from, string $to): bool
    {
        try {
            $this->driver->copy($from, $to);
        } catch (UnableToCopyFile $e) {
            return false;
        }

        return true;
    }

    /**
     * Move a file to a new location.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function move(string $from, string $to): bool
    {
        try {
            $this->driver->move($from, $to);
        } catch (UnableToMoveFile $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the file size of a given file.
     *
     * @param string $path
     * @return int
     */
    public function size(string $path): int
    {
        return $this->driver->fileSize($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @param string $path
     * @return string|false
     */
    public function mimeType(string $path): bool|string
    {
        return $this->driver->mimeType($path);
    }

    /**
     * Get the file's last modification time.
     *
     * @param string $path
     * @return int
     */
    public function lastModified(string $path): int
    {
        return $this->driver->lastModified($path);
    }

    /**
     * {@inheritdoc}
     */
    public function readStream(string $path)
    {
        try {
            return $this->driver->readStream($path);
        } catch (UnableToReadFile $e) {
            //
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream(string $path, $resource, array $options = []): bool
    {
        try {
            $this->driver->writeStream($path, $resource, $options);
        } catch (UnableToWriteFile $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     * @return string
     *
     * @throws \RuntimeException
     */
    public function url(string $path): string
    {
        $adapter = $this->adapter;

        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        }

        if (method_exists($this->driver, 'getUrl')) {
            return $this->driver->getUrl($path);
        }

        if ($adapter instanceof FtpAdapter) {
            return $this->getFtpUrl($path);
        }

        if ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);
        }

        throw new RuntimeException('This driver does not support retrieving URLs.');
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     * @return string
     */
    protected function getFtpUrl(string $path): string
    {
        return isset($this->config['url'])
            ? $this->concatPathToUrl($this->config['url'], $path)
            : $path;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     * @return string
     */
    protected function getLocalUrl(string $path): string
    {
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $path);
        }

        $path = '/storage/' . $path;

        // If the path contains "storage/public", it probably means the developer is using
        // the default disk to generate the path instead of the "public" disk like they
        // are really supposed to use. We will remove the public from this path here.
        if (Str::contains($path, '/storage/public/')) {
            return Str::replaceFirst('/public/', '/', $path);
        }

        return $path;
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param string $path
     * @param DateTimeInterface $expiration
     * @param array $options
     * @return string
     *
     * @throws \RuntimeException
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        if (!method_exists($this->adapter, 'getTemporaryUrl')) {
            throw new RuntimeException('This driver does not support creating temporary URLs.');
        }

        return $this->adapter->getTemporaryUrl($path, $expiration, $options);
    }

    /**
     * Concatenate a path to a URL.
     *
     * @param string $url
     * @param string $path
     * @return string
     */
    protected function concatPathToUrl(string $url, string $path): string
    {
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Get an array of all files in a directory.
     *
     * @param string|null $directory
     * @param bool $recursive
     * @return array
     */
    public function files(string $directory = null, bool $recursive = false): array
    {
        return $this->driver->listContents($directory, $recursive)
            ->filter(function (StorageAttributes $attributes) {
                return $attributes->isFile();
            })
            ->map(function (StorageAttributes $attributes) {
                return $attributes->path();
            })
            ->toArray();
    }

    /**
     * Get all of the files from the given directory (recursive).
     *
     * @param string|null $directory
     * @return array
     */
    public function allFiles(string $directory = null): array
    {
        return $this->files($directory, true);
    }

    /**
     * Get all of the directories within a given directory.
     *
     * @param string|null $directory
     * @param bool $recursive
     * @return array
     */
    public function directories(string $directory = null, bool $recursive = false): array
    {
        return $this->driver->listContents($directory, $recursive)
            ->filter(function (StorageAttributes $attributes) {
                return $attributes->isDir();
            })
            ->map(function (StorageAttributes $attributes) {
                return $attributes->path();
            })
            ->toArray();
    }

    /**
     * Get all (recursive) of the directories within a given directory.
     *
     * @param string|null $directory
     * @return array
     */
    public function allDirectories(string $directory = null): array
    {
        return $this->directories($directory, true);
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @return bool
     */
    public function makeDirectory(string $path): bool
    {
        try {
            $this->driver->createDirectory($path);
        } catch (UnableToCreateDirectory $e) {
            return false;
        }

        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $directory
     * @return bool
     */
    public function deleteDirectory(string $directory): bool
    {
        try {
            $this->driver->deleteDirectory($directory);
        } catch (UnableToDeleteDirectory $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the Flysystem driver.
     *
     * @return FilesystemOperator
     */
    public function getDriver(): FilesystemOperator
    {
        return $this->driver;
    }

    /**
     * Get the Flysystem adapter.
     *
     * @return FlysystemAdapter
     */
    public function getAdapter(): FlysystemAdapter
    {
        return $this->adapter;
    }

    /**
     * Get the configuration values.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Parse the given visibility value.
     *
     * @param string|null $visibility
     * @return string|null
     *
     * @throws \InvalidArgumentException
     */
    protected function parseVisibility(?string $visibility): ?string
    {
        if (is_null($visibility)) {
            return null;
        }

        switch ($visibility) {
            case FilesystemContract::VISIBILITY_PUBLIC:
                return Visibility::PUBLIC;
            case FilesystemContract::VISIBILITY_PRIVATE:
                return Visibility::PRIVATE;
        }

        throw new InvalidArgumentException("Unknown visibility: {$visibility}.");
    }

    /**
     * Pass dynamic methods call onto Flysystem.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $parameters)
    {
        if (method_exists($this->driver, $method)) {
            return $this->driver->{$method}(...array_values($parameters));
        }

        if (method_exists($this->adapter, $method)) {
            return $this->adapter->{$method}(...array_values($parameters));
        }
    }
}
