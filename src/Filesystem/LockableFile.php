<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

use Mini\Exception\LockTimeoutException;

/**
 * Class LockableFile
 * @package Mini\Filesystem
 */
class LockableFile
{
    /**
     * The file resource.
     *
     * @var resource
     */
    protected $handle;

    /**
     * The file path.
     *
     * @var string
     */
    protected string $path;

    /**
     * Indicates if the file is locked.
     *
     * @var bool
     */
    protected bool $isLocked = false;

    /**
     * Create a new File instance.
     *
     * @param string $path
     * @param string $mode
     * @return void
     */
    public function __construct(string $path, string $mode)
    {
        $this->path = $path;

        $this->ensureDirectoryExists($path);
        $this->createResource($path, $mode);
    }

    /**
     * Create the file's directory if necessary.
     *
     * @param string $path
     * @return void
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!file_exists(dirname($path))) {
            @mkdir(dirname($path), 0777, true);
        }
    }

    /**
     * Create the file resource.
     *
     * @param string $path
     * @param string $mode
     * @return void
     */
    protected function createResource(string $path, string $mode): void
    {
        $this->handle = @fopen($path, $mode);
    }

    /**
     * Read the file contents.
     *
     * @param int|null $length
     * @return string
     */
    public function read(int $length = null): string
    {
        clearstatcache(true, $this->path);

        return fread($this->handle, $length ?? ($this->size() ?: 1));
    }

    /**
     * Get the file size.
     *
     * @return int
     */
    public function size(): int
    {
        return filesize($this->path);
    }

    /**
     * Write to the file.
     *
     * @param string $contents
     */
    public function write(string $contents): self
    {
        fwrite($this->handle, $contents);

        fflush($this->handle);

        return $this;
    }

    /**
     * Truncate the file.
     *
     * @return $this
     */
    public function truncate(): self
    {
        rewind($this->handle);

        ftruncate($this->handle, 0);

        return $this;
    }

    /**
     * Get a shared lock on the file.
     *
     * @param bool $block
     * @return $this
     */
    public function getSharedLock(bool $block = false): self
    {
        if (!flock($this->handle, LOCK_SH | ($block ? 0 : LOCK_NB))) {
            throw new LockTimeoutException("Unable to acquire file lock at path [{$this->path}].");
        }

        $this->isLocked = true;

        return $this;
    }

    /**
     * Get an exclusive lock on the file.
     *
     * @param bool $block
     * @return LockableFile
     * @throws LockTimeoutException
     */
    public function getExclusiveLock(bool $block = false): self
    {
        if (!flock($this->handle, LOCK_EX | ($block ? 0 : LOCK_NB))) {
            throw new LockTimeoutException("Unable to acquire file lock at path [{$this->path}].");
        }

        $this->isLocked = true;

        return $this;
    }

    /**
     * Release the lock on the file.
     *
     * @return $this
     */
    public function releaseLock(): self
    {
        flock($this->handle, LOCK_UN);

        $this->isLocked = false;

        return $this;
    }

    /**
     * Close the file.
     *
     * @return bool
     */
    public function close(): bool
    {
        if ($this->isLocked) {
            $this->releaseLock();
        }

        return fclose($this->handle);
    }
}
