<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

use Swoole\Timer;

class Crontab
{
    /**
     * @var string
     */
    protected $rule;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var array
     */
    protected static $instances = [];

    /**
     * Crontab constructor.
     * @param $rule
     * @param $callback
     * @param null $name
     */
    public function __construct($rule, $callback, $name = null)
    {
        $this->rule = $rule;
        $this->callback = $callback;
        $this->name = $name;
        $this->id = static::createId();
        static::$instances[$this->id] = $this;
        static::tryInit();
    }

    /**
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function destroy()
    {
        return static::remove($this->id);
    }

    /**
     * @return array
     */
    public static function getAll()
    {
        return static::$instances;
    }

    /**
     * @param $id
     * @return bool
     */
    public static function remove($id)
    {
        if ($id instanceof Crontab) {
            $id = $id->getId();
        }
        if (!isset(static::$instances[$id])) {
            return false;
        }
        unset(static::$instances[$id]);
        return true;
    }

    /**
     * @return int
     */
    protected static function createId()
    {
        static $id = 0;
        return ++$id;
    }

    /**
     * tryInit
     */
    protected static function tryInit()
    {
        static $inited = false;
        if ($inited) {
            return;
        }
        $inited = true;
        $callback = function () use ($parser, &$callback) {
            foreach (static::$instances as $crontab) {
                $rule = $crontab->getRule();
                $cb = $crontab->getCallback();
                if (!$cb || !$rule) {
                    continue;
                }
                $times = Parser::parse($rule);
                $now = time();
                foreach ($times as $time) {
                    $t = $time - $now;
                    if ($t <= 0) {
                        $t = 0.000001;
                    }
                    Timer::add($t, $cb, null, false);
                }
            }
            Timer::add(60 - time() % 60, $callback, null, false);
        };

        $next_time = time() % 60;
        if ($next_time == 0) {
            $next_time = 0.00001;
        } else {
            $next_time = 60 - $next_time;
        }
        Timer::tick($next_time, $callback, null, false);
    }

}