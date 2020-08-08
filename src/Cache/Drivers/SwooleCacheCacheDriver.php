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
    /** @var Table */
    protected Table $table;
    protected $prefix;

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
        $this->table = new Table(4096, 0.2);
        $this->table->column('value', Table::TYPE_STRING, 4096);
        $this->table->column('expire', Table::TYPE_INT, 4);
        $this->table->create();
    }

    /**
     * 周期性回收
     * @param int $interval
     */
    private function recycle($interval = 1000): void
    {
        Timer::tick($interval, function () {
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
    public function get($key, $default = null)
    {
        $value = $this->table->get($this->prefix . $key);
        return $value === false ? $default : $value['value'];
    }


    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool|mixed
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->table->set($this->prefix . $key, [
            'value' => $value,
            'expire' => is_null($ttl) ? 0 : (int)$ttl + time(),
        ]);
    }

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function delete($key)
    {
        return $this->table->del($this->prefix . $key);
    }

    /**
     * @return bool|void
     */
    public function clear()
    {
        if ($this->table instanceof Table) {
            $this->table->destroy();
            unset($this->table);
        }
        $this->initTable();
    }

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function has($key)
    {
        return $this->table->exist($this->prefix . $key);
    }

    /**
     * @param $key
     * @param int $step
     * @return bool|mixed
     */
    public function inc($key, int $step = 1)
    {
        if ($this->has($key)) {
            return $this->table->incr($this->prefix . $key, 'value', $step);
        }
        return $this->set($key, $step);
    }

    /**
     * @param $key
     * @param int $step
     * @return int
     */
    public function dec($key, int $step = 1)
    {
        if ($this->has($key)) {
            return $this->table->decr($this->prefix . $key, 'value', $step);
        }
        return $this->set($key, 0 - $step);
    }

}