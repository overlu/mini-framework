<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

use ErrorException;
use FilesystemIterator;
use League\Flysystem\UnableToWriteFile;
use Mini\Exception\FileNotFoundException;
use Mini\Support\LazyCollection;
use Mini\Support\Traits\Macroable;
use RuntimeException;
use SplFileObject;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Mime\MimeTypes;

/**
 * Class Filesystem
 * @package Mini\Filesystem
 */
class Filesystem implements \Mini\Contracts\File
{
    use Macroable;

    /**
     * Determine if a file or directory exists.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
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
     * Get the contents of a file.
     *
     * @param string $path
     * @param bool $lock
     * @return string
     *
     * @throws FileNotFoundException
     */
    public function get(string $path, bool $lock = false): string
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Get contents of a file with shared access.
     *
     * @param string $path
     * @return string
     */
    public function sharedGet(string $path): string
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);
                    $contents = fread($handle, $this->size($path) ?: 1);
                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Get the returned value of a file.
     *
     * @param string $path
     * @param array $data
     * @return mixed
     *
     * @throws FileNotFoundException
     */
    public function getRequire(string $path, array $data = []): mixed
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);

                return require $__path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Require the given file once.
     *
     * @param string $path
     * @param array $data
     * @return mixed
     * @throws FileNotFoundException
     */
    public function requireOnce(string $path, array $data = []): mixed
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);

                return require_once $__path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Get the contents of a file one line at a time.
     *
     * @param string $path
     * @param int $start
     * @param int|null $num
     * @return LazyCollection
     *
     * @throws FileNotFoundException
     */
    public function lines(string $path, ?int $num = null, int $start = 1): LazyCollection
    {
        if (!$this->isFile($path)) {
            throw new FileNotFoundException(
                "File does not exist at path {$path}."
            );
        }

        $start = max($start, 1);
        $file = new SplFileObject($path, 'rb');
        $file->seek($start - 1);
        $file->setFlags(SplFileObject::DROP_NEW_LINE);
        return LazyCollection::make(static function () use ($file, $num) {
            $i = 0;
            while ($num === null ? (!$file->eof()) : ($i < $num)) {
                ++$i;
                yield $file->current();
                $file->next();
            }
        });
    }

    /**
     * @param string $path
     * @param int $num
     * @return LazyCollection
     * @throws FileNotFoundException
     */
    public function tails(string $path, int $num = 1): LazyCollection
    {
        if (!$this->isFile($path)) {
            throw new FileNotFoundException(
                "File does not exist at path {$path}."
            );
        }

        $num = max($num, 1);
        return LazyCollection::make(static function () use ($path, $num) {
            $file = new SplFileObject($path, 'rb');
            $file->setFlags(SplFileObject::DROP_NEW_LINE);
            $pos = -2;
            $eof = '';
            while ($num > 0) {
                while ($eof !== "\n") {
                    if (!$file->fseek($pos, SEEK_END)) {
                        $eof = $file->fgetc();
                        --$pos;
                    } else {
                        break;
                    }
                }
                $eof = '';
                --$num;
                yield $file->fgets();
            }
        });
    }

    /**
     * Get the MD5 hash of the file at the given path.
     *
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
     * @param string $contents
     * @param mixed $lock
     * @return bool
     */
    public function put(string $path, $contents, mixed $lock = false): bool
    {
        $dir = $this->dirname($path);
        !$this->isDirectory($dir) && $this->makeDirectory($dir, 0755, true);
        return (bool)file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * @param string $path
     * @param string $content
     * @return void
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
     *
     * @param string $path
     * @param string $data
     * @return bool
     * @throws FileNotFoundException
     */
    public function prepend(string $path, string $data, string $separator = ''): bool
    {
        if ($this->exists($path)) {
            return $this->put($path, $data . $separator . $this->get($path));
        }

        return $this->put($path, $data . $separator);
    }

    /**
     * Append to a file.
     *
     * @param string $path
     * @param string $data
     * @param string $separator
     * @return bool
     */
    public function append(string $path, string $data, string $separator = ''): bool
    {
        $dir = $this->dirname($path);
        !$this->isDirectory($dir) && $this->makeDirectory($dir, 0755, true);
        return file_put_contents($path, $data . $separator, FILE_APPEND);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     *
     * @param string $path
     * @param int|null $mode
     * @return bool|string
     */
    public function chmod(string $path, ?int $mode = null): bool|string
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
    public function delete(array|string $paths): bool
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
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function move(string $from, string $to): bool
    {
        return rename($from, $to);
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
        return copy($from, $to);
    }

    /**
     * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
     *
     * @param string $target
     * @param string $link
     * @return void
     */
    public function link(string $target, string $link): void
    {
        if (!windows_os()) {
            symlink($target, $link);
            return;
        }

        $mode = $this->isDirectory($target) ? 'J' : 'H';

        exec("mklink /{$mode} " . escapeshellarg($link) . ' ' . escapeshellarg($target));
    }

    /**
     * Create a relative symlink to the target file or directory.
     *
     * @param string $target
     * @param string $link
     * @return void
     */
    public function relativeLink(string $target, string $link): void
    {
        if (!class_exists(SymfonyFilesystem::class)) {
            throw new RuntimeException(
                'To enable support for relative links, please install the symfony/filesystem package.'
            );
        }

        $relativeTarget = (new SymfonyFilesystem)->makePathRelative($target, dirname($link));

        $this->link($relativeTarget, $link);
    }

    /**
     * Extract the file name from a file path.
     *
     * @param string $path
     * @return string
     */
    public function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     *
     * @param string $path
     * @return string
     */
    public function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     *
     * @param string $path
     * @return string
     */
    public function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     *
     * @param string $path
     * @return string
     */
    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Guess the file extension from the mime-type of a given file.
     *
     * @param string $path
     * @return string|null
     */
    public function guessExtension(string $path): ?string
    {
        if (!class_exists(MimeTypes::class)) {
            throw new RuntimeException(
                'To enable support for guessing extensions, please install the symfony/mime package.'
            );
        }

        return (new MimeTypes)->getExtensions($this->mimeType($path))[0] ?? null;
    }

    /**
     * Get the file type of given file.
     *
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
     * @return string|false
     */
    public function mimeType(string $path): bool|string
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Get the file size of a given file.
     *
     * @param string $path
     * @return int
     */
    public function size(string $path): int
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     *
     * @param string $path
     * @return int
     */
    public function lastModified(string $path): int
    {
        return filemtime($path);
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param string $directory
     * @return bool
     */
    public function isDirectory(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is readable.
     *
     * @param string $path
     * @return bool
     */
    public function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
     *
     * @param string $path
     * @return bool
     */
    public function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Determine if the given path is a file.
     *
     * @param string $file
     * @return bool
     */
    public function isFile(string $file): bool
    {
        return is_file($file);
    }

    /**
     * Find path names matching a given pattern.
     *
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
     * @return Finder
     */
    public function filesAsIterator(string $directory = '', bool $hidden = false): Finder
    {
        return Finder::create()->files()->ignoreDotFiles(!$hidden)->in($directory)->depth(0)->sortByName(true);
    }

    /**
     * Get an array of all files in a directory.
     *
     * @param string $directory
     * @param bool $hidden
     * @return SplFileInfo[]
     */
    public function files(string $directory = '', bool $hidden = false): array
    {
        return iterator_to_array(
            $this->filesAsIterator($directory, $hidden),
            false
        );
    }

    /**
     * Get all the files from the given directory (recursive).
     *
     * @param string $directory
     * @param bool $hidden
     * @return Finder
     */
    public function allFilesAsIterator(string $directory = '', bool $hidden = false): Finder
    {
        return Finder::create()->files()->ignoreDotFiles(!$hidden)->in($directory)->sortByName(true);
    }

    /**
     * Get all the files from the given directory (recursive).
     *
     * @param string $directory
     * @param bool $hidden
     * @return SplFileInfo[]
     */
    public function allFiles(string $directory = '', bool $hidden = false): array
    {
        return iterator_to_array(
            $this->allFilesAsIterator($directory, $hidden),
            false
        );
    }

    /**
     * @param string $directory
     * @param bool $hidden
     * @return array
     */
    public function allFilesToArray(string $directory = '', bool $hidden = false): array
    {
        $files = [];
        $resources = $this->allFilesAsIterator($directory, $hidden);
        foreach ($resources as $file) {
            $files[] = $file->getPathname();
        }
        return $files;
    }

    /**
     * Get all the files count from the given directory (recursive).
     *
     * @param string $directory
     * @param bool $hidden
     * @return int
     */
    public function allFilesCount(string $directory = '', bool $hidden = false): int
    {
        return count(Finder::create()->files()->ignoreDotFiles(!$hidden)->in($directory));
    }

    /**
     * Get all the directories within a given directory.
     *
     * @param string $directory
     * @return array
     */
    public function directoriesToArray(string $directory = ''): array
    {
        $directories = [];
        $resources = Finder::create()->in($directory)->directories()->depth(0)->sortByName(true);
        foreach ($resources as $dir) {
            $directories[] = $dir->getPathname();
        }
        return $directories;
    }

    /**
     * @param string $directory
     * @return Finder
     */
    public function directoriesAsIterator(string $directory = ''): Finder
    {
        return Finder::create()->in($directory)->directories()->depth(0)->sortByName(true);
    }

    /**
     * Get all the directories within a given directory.
     *
     * @param string $directory
     * @param bool $recursive
     * @return SplFileInfo[]
     */
    public function directories(string $directory = '', bool $recursive = false): array
    {
        return iterator_to_array(
            $this->directoriesAsIterator($directory),
            false
        );

    }

    /**
     * @param string $directory
     * @return Finder
     */
    public function allDirectoriesAsIterator(string $directory = ''): Finder
    {
        return Finder::create()->in($directory)->directories()->sortByName(true);
    }

    /**
     * Get all the directories within a given directory (recursive).
     *
     * @param string $directory
     * @return SplFileInfo[]
     */
    public function allDirectories(string $directory = ''): array
    {
        return iterator_to_array(
            $this->allDirectoriesAsIterator($directory),
            false
        );
    }

    /**
     * Get all the directories count from the given directory (recursive).
     *
     * @param string $directory
     * @return int
     */
    public function allDirectoriesCount(string $directory = ''): int
    {
        return count(Finder::create()->in($directory)->directories());
    }

    /**
     * Get all the directories within a given directory (recursive).
     *
     * @param string $directory
     * @return array
     */
    public function allDirectoriesToArray(string $directory = ''): array
    {
        $directories = [];

        foreach ($this->allDirectoriesAsIterator($directory) as $dir) {
            $directories[] = $dir->getPathname();
        }

        return $directories;
    }

    /**
     * Ensure a directory exists.
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return void
     */
    public function ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true): void
    {
        if (!$this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @param bool $force
     * @return bool
     */
    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Move a directory.
     *
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
     *
     * @param string $directory
     * @param string $destination
     * @param int|null $options
     * @return bool
     */
    public function copyDirectory(string $directory, string $destination, ?int $options = null): bool
    {
        if (!$this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        $this->ensureDirectoryExists($destination, 0777);

        $items = new FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            $target = $destination . '/' . $item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (!$this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            }

            // If the current items is just a regular file, we will just copy this to the new
            // location and keep looping. If for some reason the copy fails we'll bail out
            // and return false, so the developer is aware that the copy process failed.
            else if (!$this->copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
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
            // If the item is a directory, we can just recurse into the function and
            // delete that subdirectory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && !$item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all the files in this directory
            // and calling directories recursively, so we delete the real path.
            else {
                $this->delete($item->getPathname());
            }
        }

        if (!$preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Remove all the directories within a given directory.
     *
     * @param string $directory
     * @return bool
     */
    public function deleteDirectories(string $directory): bool
    {
        $allDirectories = $this->directories($directory);

        foreach ($allDirectories as $directoryName) {
            $this->deleteDirectory($directoryName->getRealPath());
        }

        return true;
    }

    /**
     * Empty the specified directory of all files and folders.
     *
     * @param string $directory
     * @return bool
     */
    public function cleanDirectory(string $directory): bool
    {
        return $this->deleteDirectory($directory, true);
    }

    /**
     * @param string $path
     * @return resource|null
     * @throws FileNotFoundException
     */
    public function readStream(string $path)
    {
        $resource = fopen('php://temp', 'rb+');
        fwrite($resource, $this->get($path));
        fseek($resource, 0);
        return $resource;
    }

    /**
     * @param string $path
     * @param $resource
     * @param array $options
     * @return bool
     */
    public function writeStream(string $path, $resource, array $options = []): bool
    {
        if (!is_resource($resource)) {
            throw UnableToWriteFile::atLocation($path, 'The contents is invalid resource.');
        }
        while (!feof($resource)) {
            if (false === $buffer = fread($resource, 1048576)) { // 1024 * 1024
                throw UnableToWriteFile::atLocation($path, 'fread failed');
            }
            $this->put($path, $buffer);
        }
        fclose($resource);
        return true;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getVisibility(string $path): string
    {
        return static::VISIBILITY_PUBLIC;
    }

    /**
     * @param string $path
     * @param string $visibility
     * @return bool
     */
    public function setVisibility(string $path, string $visibility): bool
    {
        return true;
    }
}
