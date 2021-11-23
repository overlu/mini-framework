<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Watch;

use Mini\Server;
use Mini\Support\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class WatchServer
{

    protected array $watch_dir = [];

    protected array $hashes = [];

    public function __construct()
    {
        $this->watch_dir = (array)config('app.watch_dir', []);
    }

    public function watch(): void
    {
        foreach ($this->hashes as $pathName => $currentHash) {
            if (!file_exists($pathName)) {
                unset($this->hashes[$pathName]);
                continue;
            }
            $newHash = $this->fileHash($pathName);
            if ($newHash !== $currentHash) {
                $this->change();
                $this->state();
                break;
            }
        }
    }

    public function state(): void
    {
        $files = [];
        foreach ($this->watch_dir as $dir) {
            $files = array_merge($files, $this->phpFiles($dir));
        }
        $this->hashes = array_combine($files, array_map([$this, 'fileHash'], $files));
        $count = count($this->hashes);
        Command::infoWithTime('📡 watching [' . $count . '] files...');
    }

    protected function change(): void
    {
        Command::infoWithTime('🔄 reload...');
        Server::getInstance()->reload();
    }

    protected function fileHash(string $pathname): string
    {
        $contents = @file_get_contents($pathname);
        if (false === $contents) {
            return 'deleted';
        }
        return md5($contents);
    }

    /**
     * @param string $dirname
     * @return array
     */
    protected function phpFiles(string $dirname): array
    {
        $dir = BASE_PATH . DIRECTORY_SEPARATOR . trim($dirname, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $directory = new RecursiveDirectoryIterator($dir);
        $filter = new Filter($directory);
        $iterator = new RecursiveIteratorIterator($filter);
        return array_map(static function (\SplFileInfo $fileInfo) {
            return $fileInfo->getPathname();
        }, iterator_to_array($iterator));
    }
}






