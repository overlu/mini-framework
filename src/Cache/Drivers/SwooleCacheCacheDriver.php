<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

use Swoole\Table;
use Swoole\Timer;

/**
 * Class SwooleTable
 * @package Mini\Cache\Drivers\
 */
class SwooleCacheCacheDriver extends AbstractCacheDriver
{
    protected Table $table;

    /**
     * SwooleTable constructor.
     */
    public function __construct()
    {
        $this->prefix = config('cache.prefix', '');
        $this->initTable();
        $this->recycle();
    }

    private function initTable(): void
    {
        $this->table = server()->table;
    }

    /**
     * 周期性回收
     */
    private function recycle(): void
    {
        Timer::tick(1000, function () {
            $time = time();
            foreach ($this->table as $key => $item) {
                if ($item['expire'] !== 0 && $item['expire'] < $time) {
                    $this->table->del($key);
                }
            }
        });
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        $value = $this->table->get($this->prefix . $key);
        return $value === false ? $default : unserialize($value['value'], ["allowed_classes" => true]);
    }


    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if ($ttl <= 0 && !is_null($ttl)) {
            return $this->delete($key);
        }
        return (bool)$this->table->set($this->prefix . $key, [
            'value' => serialize($value),
            'expire' => is_null($ttl) ? 0 : $ttl + time(),
        ]);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return (bool)$this->table->del($this->prefix . $key);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        if ($this->table instanceof Table) {
            $this->table->destroy();
            unset($this->table);
        }
        $this->initTable();
        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return (bool)$this->table->exist($this->prefix . $key);
    }

    /**
     * @param string $key
     * @param int $step
     * @return bool|mixed
     */
    public function inc(string $key, int $step = 1): int
    {
        return (int)$this->table->incr($this->prefix . $key, 'value', $step);
    }

    /**
     * @param string $key
     * @param int $step
     * @return int
     */
    public function dec(string $key, int $step = 1): int
    {
        return (int)$this->table->decr($this->prefix . $key, 'value', $step);
    }

}