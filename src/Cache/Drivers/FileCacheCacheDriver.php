<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

use RuntimeException;

class FileCacheCacheDriver extends AbstractCacheDriver
{
    protected string $path;

    public function __construct()
    {
        $this->path = rtrim(config('cache.drivers.file.path', BASE_PATH . '/storage/cache'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->prefix = config('cache.prefix', '');
        if (!is_dir($this->path) && !mkdir($concurrentDirectory = $this->path, 0755, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getCacheKey(string $name): string
    {
        return $this->path . ($this->prefix ? $this->prefix . '.' : '') . md5($name) . '.cache';
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $created_at = time();
        if ($ttl <= 0 && !is_null($ttl)) {
            return $this->delete($key);
        }
        $data = [
            'content' => $value,
            'created_at' => $created_at
        ];
        if ($ttl > 0) {
            $data['ttl'] = $ttl;
        }
        return $this->setContent($key, $data);
    }

    /**
     * @param string $key
     * @param array $data
     * @return bool
     */
    protected function setContent(string $key, array $data): bool
    {
        $filename = $this->getCacheKey($key);
        $new_data = serialize($data);
        $result = file_put_contents($filename, $new_data, LOCK_EX);
        return (bool)$result;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->getContent($key, $default);
        return $data === $default ? $data : $data['content'];
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    protected function getContent(string $key, mixed $default = null): mixed
    {
        $filename = $this->getCacheKey($key);
        if (!is_file($filename)) {
            return $default;
        }
        $content = @file_get_contents($filename);
        if (false !== $content) {
            $content = unserialize($content, ["allowed_classes" => true]);
            if (isset($content['ttl']) && ($content['created_at'] + $content['ttl']) < time()) {
                $this->unlink($filename);
                return $default;
            }
            return $content;
        }
        return $default;
    }

    /**
     * @param string $key
     * @param int $step
     * @return int
     */
    public function inc(string $key, int $step = 1): int
    {
        if (($value = $this->getContent($key)) && is_int($value['content'])) {
            $value['content'] += $step;
        } else {
            $value = [
                'content' => $step,
                'created_at' => time()
            ];
        }
        $this->setContent($key, $value);
        return $value['content'];
    }

    /**
     * @param string $key
     * @param int $step
     * @return int
     */
    public function dec(string $key, int $step = 1): int
    {
        if (($value = $this->getContent($key)) && is_int($value['content'])) {
            $value['content'] -= $step;
        } else {
            $value = [
                'content' => -$step,
                'created_at' => time()
            ];
        }
        $this->setContent($key, $value);
        return $value['content'];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return (bool)$this->getContent($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $filename = $this->getCacheKey($key);
        return $this->unlink($filename);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return (bool)app('files')->cleanDirectory($this->path);
    }

    /**
     * @param string $path
     * @return bool
     */
    private function unlink(string $path): bool
    {
        return is_file($path) && unlink($path);
    }
}