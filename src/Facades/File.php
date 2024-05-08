<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * @method static \Symfony\Component\Finder\Finder allFilesAsIterator(string $directory, bool $hidden = false)
 * @method static \Symfony\Component\Finder\SplFileInfo[] allFiles(string $directory, bool $hidden = false)
 * @method static array allFilesToArray(string $directory, bool $hidden = false)
 * @method static int allFilesCount(string $directory, bool $hidden = false)
 * @method static \Symfony\Component\Finder\Finder filesAsIterator(string $directory, bool $hidden = false)
 * @method static \Symfony\Component\Finder\SplFileInfo[] files(string $directory, bool $hidden = false)
 * @method static array directories(string $directory)
 * @method static \Symfony\Component\Finder\Finder directoriesAsIterator(string $directory)
 * @method static \Symfony\Component\Finder\SplFileInfo[] allDirectories(string $directory)
 * @method static \Symfony\Component\Finder\Finder allDirectoriesAsIterator(string $directory)
 * @method static array directoriesToArray(string $directory)
 * @method static array allDirectoriesToArray(string $directory)
 * @method static int allDirectoriesCount(string $directory)
 * @method static array glob(string $pattern, int $flags = 0)
 * @method static bool cleanDirectory(string $directory)
 * @method static bool copy(string $path, string $target)
 * @method static bool copyDirectory(string $directory, string $destination, int|null $options = null)
 * @method static bool delete(string|array $paths)
 * @method static bool deleteDirectories(string $directory)
 * @method static bool deleteDirectory(string $directory, bool $preserve = false)
 * @method static bool exists(string $path)
 * @method static bool isDirectory(string $directory)
 * @method static bool isFile(string $file)
 * @method static bool isReadable(string $path)
 * @method static bool isWritable(string $path)
 * @method static bool makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false)
 * @method static bool move(string $path, string $target)
 * @method static bool moveDirectory(string $from, string $to, bool $overwrite = false)
 * @method static bool missing(string $path)
 * @method static int append(string $path, string $data)
 * @method static int lastModified(string $path)
 * @method static \Mini\Support\LazyCollection lines(string $path, ?int $num = null, int $start = 1)
 * @method static int prepend(string $path, string $data)
 * @method static int size(string $path)
 * @method static int|bool put(string $path, string $contents, bool $lock = false)
 * @method static mixed chmod(string $path, int|null $mode = null)
 * @method static mixed getRequire(string $path, array $data = [])
 * @method static mixed requireOnce(string $path, array $data = [])
 * @method static string basename(string $path)
 * @method static string dirname(string $path)
 * @method static string extension(string $path)
 * @method static string|null guessExtension(string $path)
 * @method static string get(string $path, bool $lock = false)
 * @method static string hash(string $path)
 * @method static string name(string $path)
 * @method static string sharedGet(string $path)
 * @method static \Mini\Support\LazyCollection tails(string $path, int $num = 1)
 * @method static string type(string $path)
 * @method static string|false mimeType(string $path)
 * @method static void ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true)
 * @method static void link(string $target, string $link)
 * @method static void relativeLink(string $target, string $link)
 * @method static void replace(string $path, string $content)
 *
 * @see \Mini\Filesystem\Filesystem
 */
class File extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'files';
    }
}
