<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

class FileCacheCacheDriver extends AbstractCacheDriver
{
    protected string $path;

    public function __construct()
    {
        $this->path = rtrim(config('cache.drivers.file.path', BASE_PATH . '/storage/cache'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->prefix = config('cache.prefix', '');
        if (!is_dir($this->path) && !mkdir($concurrentDirectory = $this->path, 0755, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getCacheKey(string $name): string
    {
        return $this->path . $this->prefix . md5($name) . '.cache';
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null): bool
    {
        $created_at = time();
        $data = [
            'content' => $value,
            'created_at' => $created_at
        ];
        if ($ttl) {
            $data['ttl'] = (int)$ttl;
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
        $new_data = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents($filename, $new_data, LOCK_EX);
        return $result ? true : false;
    }

    /**
     * @param string $key
     * @param null $default
     * @return bool|mixed|string|null
     */
    public function get($key, $default = null)
    {
        $data = $this->getContent($key, $default);
        return $data === $default ? $data : $data['content'];
    }

    /**
     * @param $key
     * @param null $default
     * @return bool|mixed|string|null
     */
    protected function getContent($key, $default = null)
    {
        $filename = $this->getCacheKey($key);
        if (!is_file($filename)) {
            return $default;
        }
        $content = @file_get_contents($filename);
        if (false !== $content) {
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (isset($content['ttl']) && ($content['created_at'] + $content['ttl']) < time()) {
                $this->unlink($filename);
                return $default;
            }
            return $content;
        }
        return $default;
    }

    /**
     * @param $key
     * @param int $step
     * @return bool|int|mixed|string
     */
    public function inc($key, int $step = 1)
    {
        if ($value = $this->getContent($key)) {
            $value['content'] = $value['content'] + $step;
        } else {
            $value = [
                'content' => $step,
                'created_at' => time()
            ];
        }
        return $this->setContent($key, $value) ? $value['content'] : false;
    }

    /**
     * @param $key
     * @param int $step
     * @return bool|int|mixed|string
     */
    public function dec($key, int $step = 1)
    {
        if ($value = $this->getContent($key)) {
            $value['content'] = $value['content'] - $step;
        } else {
            $value = [
                'content' => -$step,
                'created_at' => time()
            ];
        }
        return $this->setContent($key, $value) ? $value['content'] : false;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        return $this->getContent($key) ? true : false;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key): bool
    {
        $filename = $this->getCacheKey($key);
        return $this->unlink($filename);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return app('files')->cleanDirectory($this->path);
    }

    /**
     * @param $path
     * @return bool
     */
    private function unlink($path): bool
    {
        return is_file($path) && unlink($path);
    }
}