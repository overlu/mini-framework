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
     * @param string $session_id the session ID being destroyed
     * @return bool
     */
    public function destroy($session_id): bool
    {
        $this->files->delete($this->path . '/' . $session_id);
        return true;
    }

    /**
     * Cleanup old sessions.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime): bool
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
     * @param string $save_path the path where to store/retrieve the session
     * @param string $name the session name
     * @return bool
     */
    public function open($save_path, $name): bool
    {
        return true;
    }

    /**
     * Read session data.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $session_id the session id to read data for
     * @return string
     */
    public function read($session_id): string
    {
        if (
            $this->files->isFile($path = $this->path . '/' . $session_id)
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
     * @param string $session_id the session id
     * @param string $session_data
     * @return bool
     */
    public function write($session_id, $session_data): bool
    {
        $this->files->put($this->path . '/' . $session_id, $session_data, true);
        return true;
    }
}
