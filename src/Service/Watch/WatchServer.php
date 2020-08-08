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
    protected string $watch_dir = BASE_PATH . '/app/';

    protected array $hashes = [];

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
        $files = $this->phpFiles($this->watch_dir);
        $this->hashes = array_combine($files, array_map([$this, 'fileHash'], $files));
        $count = count($this->hashes);
        Command::infoWithTime("📡 watching [{$count}] files...");
    }

    protected function change(): void
    {
        Command::infoWithTime("🔄 reload...");
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
        $directory = new RecursiveDirectoryIterator($dirname);
        $filter = new Filter($directory);
        $iterator = new RecursiveIteratorIterator($filter);
        return array_map(static function (\SplFileInfo $fileInfo) {
            return $fileInfo->getPathname();
        }, iterator_to_array($iterator));
    }
}






