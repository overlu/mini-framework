<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Swoole\Coroutine;

trait CoroutineSingleTon
{
    private static array $instance = [];

    public static function getInstance(...$args)
    {
        $cid = Coroutine::getCid();
        if (!isset(self::$instance[$cid])) {
            self::$instance[$cid] = new static(...$args);
            /*
             * 兼容非携程环境
             */
            if ($cid > 0) {
                Coroutine::defer(function () use ($cid) {
                    unset(self::$instance[$cid]);
                });
            }
        }
        return self::$instance[$cid];
    }

    public function destroy(int $cid = null): void
    {
        if ($cid === null) {
            $cid = Coroutine::getCid();
        }
        unset(self::$instance[$cid]);
    }
}
