<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use ErrorException;
use FilesystemIterator;
use Mini\Exceptions\FileNotFoundException;
use Mini\Support\Traits\Macroable;
use Symfony\Component\Finder\Finder;

class Filesystem
{
    use Macroable;

    /**
     * Determine if a file or directory exists.
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @param bool $lock
     * @return string
     * @throws FileNotFoundException
     */
    public function get(string $path, bool $lock = false): string
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Get contents of a file with shared access.
     * @param string $path
     * @return string
     */
    public function sharedGet(string $path): string
    {
        return $this->atomic($path, function ($path) {
            $contents = '';
            $handle = fopen($path, 'rb');
            if ($handle) {
                $wouldBlock = false;
                flock($handle, LOCK_SH | LOCK_NB, $wouldBlock);
                while ($wouldBlock) {
                    usleep(1000);
                    flock($handle, LOCK_SH | LOCK_NB, $wouldBlock);
                }
                try {
                    clearstatcache(true, $path);
                    $contents = fread($handle, $this->size($path) ?: 1);
                } finally {
                    flock($handle, LOCK_UN);
                    fclose($handle);
                }
            }
            return $contents;
        });
    }

    /**
     * Get the returned value of a file.
     * @param string $path
     * @param array $data
     * @return mixed
     * @throws FileNotFoundException
     */
    public function getRequire($path, array $data = [])
    {
        if ($this->isFile($path)) {
            return (static function () use ($path, $data) {
                extract($data, EXTR_SKIP);

                return require $path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Require the given file once.
     *
     * @param string $file
     * @return mixed
     */
    public function requireOnce(string $file)
    {
        require_once $file;
    }

    /**
     * Get the MD5 hash of the file at the given path.
     * @param string $path
     * @return string
     */
    public function hash(string $path): string
    {
        return md5_file($path);
    }

    /**
     * Write the contents of a file.
     *
     * @param string $path
     * @param resource|string $contents
     * @param bool $lock
     * @return bool|int
     */
    public function put(string $path, $contents, bool $lock = false)
    {
        if ($lock) {
            return $this->atomic($path, static function ($path) use ($contents, $lock) {
                $handle = fopen($path, 'w+');
                if ($handle) {
                    $wouldBlock = false;
                    flock($handle, LOCK_EX | LOCK_NB, $wouldBlock);
                    while ($wouldBlock) {
                        usleep(1000);
                        flock($handle, LOCK_EX | LOCK_NB, $wouldBlock);
                    }
                    try {
                        fwrite($handle, $contents);
                    } finally {
                        flock($handle, LOCK_UN);
                        fclose($handle);
                    }
                }
            });
        }
        return file_put_contents($path, $contents);
    }

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     * @param string $path
     * @param string $content
     */
    public function replace(string $path, string $content): void
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    /**
     * Prepend to a file.
     * @param string $path
     * @param string $data
     * @return int
     * @throws FileNotFoundException
     */
    public function prepend(string $path, string $data): int
    {
        if ($this->exists($path)) {
            return $this->put($path, $data . $this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file.
     * @param string $path
     * @param string $data
     * @return int
     */
    public function append(string $path, string $data): int
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     * @param string $path
     * @param int|null $mode
     * @return bool|string
     */
    public function chmod(string $path, ?int $mode = null)
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Delete the file at a given path.
     *
     * @param array|string $paths
     * @return bool
     */
    public function delete($paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (!@unlink($path)) {
                    $success = false;
                }
            } catch (ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Move a file to a new location.
     * @param string $path
     * @param string $target
     * @return bool
     */
    public function move(string $path, string $target): bool
    {
        return rename($path, $target);
    }

    /**
     * Copy a file to a new location.
     * @param string $path
     * @param string $target
     * @return bool
     */
    public function copy(string $path, string $target): bool
    {
        return copy($path, $target);
    }

    /**
     * Create a hard link to the target file or directory.
     * @param string $target
     * @param string $link
     * @return bool
     */
    public function link(string $target, string $link)
    {
        if (!$this->windowsOs()) {
            return symlink($target, $link);
        }

        $mode = $this->isDirectory($target) ? 'J' : 'H';

        exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
    }

    /**
     * Extract the file name from a file path.
     * @param string $path
     * @return string
     */
    public function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     * @param string $path
     * @return string
     */
    public function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     * @param string $path
     * @return string
     */
    public function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     * @param string $path
     * @return string
     */
    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file type of a given file.
     * @param string $path
     * @return string
     */
    public function type(string $path): string
    {
        return filetype($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @param string $path
     * @return false|string
     */
    public function mimeType(string $path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Get the file size of a given file.
     * @param string $path
     * @return int
     */
    public function size(string $path): int
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     * @param string $path
     * @return int
     */
    public function lastModified(string $path): int
    {
        return filemtime($path);
    }

    /**
     * Determine if the given path is a directory.
     * @param string $directory
     * @return bool
     */
    public function isDirectory(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is readable.
     * @param string $path
     * @return bool
     */
    public function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
     * @param string $path
     * @return bool
     */
    public function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Determine if the given path is a file.
     * @param string $file
     * @return bool
     */
    public function isFile(string $file): bool
    {
        return is_file($file);
    }

    /**
     * Find path names matching a given pattern.
     * @param string $pattern
     * @param int $flags
     * @return array
     */
    public function glob(string $pattern, int $flags = 0): array
    {
        return glob($pattern, $flags);
    }

    /**
     * Get an array of all files in a directory.
     *
     * @param string $directory
     * @param bool $hidden
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public function files(string $directory, bool $hidden = false): array
    {
        return iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(!$hidden)->in($directory)->depth(0)->sortByName(),
            false
        );
    }

    /**
     * Get all of the files from the given directory (recursive).
     * @param string $directory
     * @param bool $hidden
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public function allFiles(string $directory, bool $hidden = false): array
    {
        return iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(!$hidden)->in($directory)->sortByName(),
            false
        );
    }

    /**
     * Get all of the directories within a given directory.
     * @param string $directory
     * @return array
     */
    public function directories(string $directory): array
    {
        $directories = [];

        foreach (Finder::create()->in($directory)->directories()->depth(0)->sortByName() as $dir) {
            $directories[] = $dir->getPathname();
        }

        return $directories;
    }

    /**
     * Create a directory.
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @param bool $force
     * @return bool
     */
    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
    {
        return $force ? @mkdir($path, $mode, $recursive) : mkdir($path, $mode, $recursive);
    }

    /**
     * Move a directory.
     * @param string $from
     * @param string $to
     * @param bool $overwrite
     * @return bool
     */
    public function moveDirectory(string $from, string $to, bool $overwrite = false): bool
    {
        if ($overwrite && $this->isDirectory($to) && !$this->deleteDirectory($to)) {
            return false;
        }
        return @rename($from, $to) === true;
    }

    /**
     * Copy a directory from one location to another.
     * @param string $directory
     * @param string $destination
     * @param int|null $options
     * @return bool
     */
    public function copyDirectory(string $directory, string $destination, int $options = null): bool
    {
        if (!$this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        if (!$this->isDirectory($destination)) {
            $this->makeDirectory($destination, 0777, true);
        }

        $items = new FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            $target = $destination . '/' . $item->getBasename();
            if ($item->isDir()) {
                $path = $item->getPathname();
                if (!$this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            } else {
                if (!$this->copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     * @param string $directory
     * @param bool $preserve
     * @return bool
     */
    public function deleteDirectory(string $directory, bool $preserve = false): bool
    {
        if (!$this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {

            if ($item->isDir() && !$item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            } else {
                $this->delete($item->getPathname());
            }
        }

        if (!$preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Remove all of the directories within a given directory.
     * @param string $directory
     * @return bool
     */
    public function deleteDirectories(string $directory): bool
    {
        $allDirectories = $this->directories($directory);

        if (!empty($allDirectories)) {
            foreach ($allDirectories as $directoryName) {
                $this->deleteDirectory($directoryName);
            }
            return true;
        }
        return false;
    }

    /**
     * Empty the specified directory of all files and folders.
     * @param string $directory
     * @return bool
     */
    public function cleanDirectory(string $directory): bool
    {
        return $this->deleteDirectory($directory, true);
    }

    /**
     * Detect whether it's Windows.
     */
    public function windowsOs(): bool
    {
        return stripos(PHP_OS, 'win') === 0;
    }

    protected function atomic($path, $callback)
    {
        if (Coroutine::inCoroutine()) {
            try {
                while (!Coroutine\Locker::lock($path)) {
                    usleep(1000);
                }
                return $callback($path);
            } finally {
                Coroutine\Locker::unlock($path);
            }
        } else {
            return $callback($path);
        }
    }
}
