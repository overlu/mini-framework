<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

use Mini\Contracts\Filesystem\Cloud as CloudFilesystemContract;
use Mini\Contracts\Filesystem\Filesystem as FilesystemContract;
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
     * @var \League\Flysystem\FilesystemOperator
     */
    protected $driver;

    /**
     * The Flysystem adapter implementation.
     *
     * @var \League\Flysystem\FilesystemAdapter
     */
    protected $adapter;

    /**
     * The filesystem configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * The Flysystem PathPrefixer instance.
     *
     * @var \League\Flysystem\PathPrefixer
     */
    protected $prefixer;

    /**
     * Create a new filesystem adapter instance.
     *
     * @param \League\Flysystem\FilesystemOperator $driver
     * @param \League\Flysystem\FilesystemAdapter $adapter
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
     * @param string|array $path
     * @param string|null $content
     * @return $this
     */
    public function assertExists($path, $content = null): self
    {
        $paths = Arr::wrap($path);

        foreach ($paths as $path) {
            PHPUnit::assertTrue(
                $this->exists($path), "Unable to find a file at path [{$path}]."
            );

            if (!is_null($content)) {
                $actual = $this->get($path);

                PHPUnit::assertSame(
                    $content,
                    $actual,
                    "File [{$path}] was found, but content [{$actual}] does not match [{$content}]."
                );
            }
        }

        return $this;
    }

    /**
     * Assert that the given file does not exist.
     *
     * @param string|array $path
     * @return $this
     */
    public function assertMissing($path): self
    {
        $paths = Arr::wrap($path);

        foreach ($paths as $path) {
            PHPUnit::assertFalse(
                $this->exists($path), "Found unexpected file at path [{$path}]."
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
    public function exists($path): bool
    {
        return $this->driver->fileExists($path);
    }

    /**
     * Determine if a file or directory is missing.
     *
     * @param string $path
     * @return bool
     */
    public function missing($path): bool
    {
        return !$this->exists($path);
    }

    /**
     * Get the full path for the file at the given "short" path.
     *
     * @param string $path
     * @return string
     */
    public function path($path): string
    {
        return $this->prefixer->prefixPath($path);
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @return string|null
     */
    public function get($path): ?string
    {
        try {
            return $this->driver->read($path);
        } catch (UnableToReadFile $e) {
            return null;
        }
    }

    /**
     * Create a streamed response for a given file.
     *
     * @param string $path
     * @param string|null $name
     * @param array|null $headers
     * @param string|null $disposition
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function response($path, $name = null, array $headers = [], $disposition = 'inline')
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
     * @param array|null $headers
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download($path, $name = null, array $headers = [])
    {
        return $this->response($path, $name, $headers, 'attachment');
    }

    /**
     * Convert the string to ASCII characters that are equivalent to the given name.
     *
     * @param string $name
     * @return string
     */
    protected function fallbackName($name)
    {
        return str_replace('%', '', Str::ascii($name));
    }

    /**
     * Write the contents of a file.
     *
     * @param string $path
     * @param string|resource $contents
     * @param mixed $options
     * @return bool
     */
    public function put($path, $contents, $options = [])
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
     * @param File|\Mini\Http\UploadedFile|string $file
     * @param mixed $options
     * @return string|false
     */
    public function putFile($path, $file, $options = [])
    {
        $file = is_string($file) ? new File($file) : $file;

        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    /**
     * Store the uploaded file on the disk with a given name.
     *
     * @param string $path
     * @param \Mini\Http\File|\Mini\Http\UploadedFile|string $file
     * @param string $name
     * @param mixed $options
     * @return string|false
     */
    public function putFileAs($path, $file, $name, $options = [])
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
    public function getVisibility($path)
    {
        if ($this->driver->visibility($path) == Visibility::PUBLIC) {
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
    public function setVisibility($path, $visibility)
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
    public function prepend($path, $data, $separator = PHP_EOL)
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
     * @param string $separator
     * @return bool
     */
    public function append($path, $data, $separator = PHP_EOL)
    {
        if ($this->exists($path)) {
            return $this->put($path, $this->get($path) . $separator . $data);
        }

        return $this->put($path, $data);
    }

    /**
     * Delete the file at a given path.
     *
     * @param string|array $paths
     * @return bool
     */
    public function delete($paths)
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
    public function copy($from, $to)
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
    public function move($from, $to)
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
    public function size($path)
    {
        return $this->driver->fileSize($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @param string $path
     * @return string|false
     */
    public function mimeType($path)
    {
        return $this->driver->mimeType($path);
    }

    /**
     * Get the file's last modification time.
     *
     * @param string $path
     * @return int
     */
    public function lastModified($path)
    {
        return $this->driver->lastModified($path);
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
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
    public function writeStream($path, $resource, array $options = [])
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
    public function url($path)
    {
        $adapter = $this->adapter;

        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        } elseif (method_exists($this->driver, 'getUrl')) {
            return $this->driver->getUrl($path);
        } elseif ($adapter instanceof FtpAdapter) {
            return $this->getFtpUrl($path);
        } elseif ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);
        } else {
            throw new RuntimeException('This driver does not support retrieving URLs.');
        }
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     * @return string
     */
    protected function getFtpUrl($path)
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
    protected function getLocalUrl($path)
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
     * @param \DateTimeInterface $expiration
     * @param array $options
     * @return string
     *
     * @throws \RuntimeException
     */
    public function temporaryUrl($path, $expiration, array $options = [])
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
    protected function concatPathToUrl($url, $path)
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
    public function files($directory = null, $recursive = false)
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
    public function allFiles($directory = null)
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
    public function directories($directory = null, $recursive = false)
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
    public function allDirectories($directory = null)
    {
        return $this->directories($directory, true);
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @return bool
     */
    public function makeDirectory($path)
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
    public function deleteDirectory($directory)
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
     * @return \League\Flysystem\FilesystemOperator
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get the Flysystem adapter.
     *
     * @return \League\Flysystem\FilesystemAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Get the configuration values.
     *
     * @return array
     */
    public function getConfig()
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
    protected function parseVisibility($visibility)
    {
        if (is_null($visibility)) {
            return;
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
    public function __call($method, array $parameters)
    {
        return $this->driver->{$method}(...array_values($parameters));
    }
}
