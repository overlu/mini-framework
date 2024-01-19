<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session\Drivers;

use Carbon\Carbon;
use Mini\Filesystem\Filesystem;
use SessionHandlerInterface;
use Symfony\Component\Finder\Finder;

class FileSessionDriver implements SessionHandlerInterface
{
    /**
     * The number of minutes the session should be valid.
     *
     * @var int
     */
    protected int $minutes;

    /**
     * @var Filesystem
     */
    private Filesystem $files;

    /**
     * The path where sessions should be stored.
     *
     * @var string
     */
    private string $path;

    public function __construct()
    {
        $this->files = app('files');
        $this->path = config('session.files');
        if (!$this->path) {
            throw new \InvalidArgumentException('Invalid session path.');
        }
        $this->minutes = (int)config('session.lifetime', 120);
        if (!file_exists($this->path)) {
            $this->files->makeDirectory($this->path, 0755, true);
        }
    }

    /**
     * Close the session.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Destroy a session.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $id the session ID being destroyed
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $this->files->delete($this->path . '/' . $id);
        return true;
    }

    /**
     * Cleanup old sessions.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $max_lifetime
     * @return bool
     */
    public function gc(int $max_lifetime): bool
    {
        $files = Finder::create()
            ->in($this->path)
            ->files()
            ->ignoreDotFiles(true)
            ->date('<= now - ' . $this->minutes * 60 . ' seconds');

        foreach ($files as $file) {
            $this->files->delete($file->getRealPath());
        }
        return true;
    }

    /**
     * Initialize session.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $path the path where to store/retrieve the session
     * @param string $name the session name
     * @return bool
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Read session data.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $id the session id to read data for
     * @return string
     */
    public function read(string $id): string
    {
        if (
            $this->files->isFile($path = $this->path . '/' . $id)
            && $this->files->lastModified($path) >= Carbon::now()->subMinutes($this->minutes)->getTimestamp()
        ) {
            return $this->files->sharedGet($path);
        }
        return '';
    }

    /**
     * Write session data.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $id the session id
     * @param string $data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        $this->files->put($this->path . '/' . $id, $data, true);
        return true;
    }
}
