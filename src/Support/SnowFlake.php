<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

class SnowFlake
{
    private static float $lastTimestamp = 0.0;
    private static int $lastSequence = 0;
    private static int $sequenceMask = 4095;
    private static int $twepoch = 1508945092000;

    /**
     * 生成基于雪花算法的随机编号
     * @param int $dataCenterID 数据中心ID 0-31
     * @param int $workerID 任务进程ID 0-31
     * @return string 分布式ID
     * @author : evalor <master@evalor.cn>
     */
    public static function make(int $dataCenterID = 0, int $workerID = 0): string
    {
        $dataCenterID = $dataCenterID > 0 ? $dataCenterID : ((int)env('CENTER_ID'));
        $workerID = $workerID > 0 ? $workerID : ((int)server()->getWorkerId());
        // 41bit timestamp + 5bit dataCenter + 5bit worker + 12bit
        $timestamp = self::timeGen();
        if (self::$lastTimestamp === $timestamp) {
            self::$lastSequence = (self::$lastSequence + 1) & self::$sequenceMask;
            if (self::$lastSequence === 0) {
                $timestamp = self::tilNextMillis(self::$lastTimestamp);
            }
        } else {
            self::$lastSequence = 0;
        }
        self::$lastTimestamp = $timestamp;
        return (string)((($timestamp - self::$twepoch) << 22) | ($dataCenterID << 17) | ($workerID << 12) | self::$lastSequence);
    }

    /**
     * 反向解析雪花算法生成的编号
     * @param float|int|string $snowFlakeId
     * @return \stdClass
     * @author : evalor <master@evalor.cn>
     */
    public static function unmake(float|int|string $snowFlakeId): \stdClass
    {
        $Binary = str_pad(decbin((int)$snowFlakeId), 64, '0', STR_PAD_LEFT);
        $Object = new \stdClass;
        $Object->timestamp = bindec(substr($Binary, 0, 42)) + self::$twepoch;
        $Object->dataCenterID = bindec(substr($Binary, 42, 5));
        $Object->workerID = bindec(substr($Binary, 47, 5));
        $Object->sequence = bindec(substr($Binary, -12));
        return $Object;
    }

    /**
     * 等待下一毫秒的时间戳
     * @param $lastTimestamp
     * @return float
     * @author : evalor <master@evalor.cn>
     */
    private static function tilNextMillis($lastTimestamp): float
    {
        $timestamp = self::timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = self::timeGen();
        }
        return $timestamp;
    }

    /**
     * 获取毫秒级时间戳
     * @return float
     * @author : evalor <master@evalor.cn>
     */
    private static function timeGen(): float
    {
        return (float)sprintf('%.0f', microtime(true) * 1000);
    }
}
