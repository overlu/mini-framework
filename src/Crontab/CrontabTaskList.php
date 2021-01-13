<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

/**
 * Class CrontabTaskList
 * @package Mini\Crontab
 */
class CrontabTaskList
{
    /**
     * @var CrontabTaskInterface[]
     */
    private static array $crontabTaskList = [];

    /**
     * 获取所有的任务
     * @return CrontabTaskInterface[]
     */
    public static function getCrontabTaskList(): array
    {
        return self::$crontabTaskList;
    }

    /**
     * 获取任务
     * @param string $name
     * @return CrontabTaskInterface|null
     */
    public static function getTask(string $name): ?CrontabTaskInterface
    {
        return self::$crontabTaskList[$name] ?? null;
    }

    /**
     * 新增任务，不会覆盖原有任务
     * @param string $name
     * @param CrontabTaskInterface $handle
     */
    public static function addTask(string $name, CrontabTaskInterface $handle): void
    {
        self::$crontabTaskList[$name] || self::$crontabTaskList[$name] = $handle;
    }

    /**
     * 新增任务，会覆盖原有任务
     * @param string $name
     * @param CrontabTaskInterface $handle
     */
    public static function setTask(string $name, CrontabTaskInterface $handle): void
    {
        self::$crontabTaskList[$name] = $handle;
    }

    /**
     * 移除任务
     * @param string $name
     */
    public static function removeTask(string $name): void
    {
        if (self::$crontabTaskList[$name]) {
            unset(self::$crontabTaskList[$name]);
        }
    }

    /**
     * 清空任务
     */
    public static function truncate(): void
    {
        self::$crontabTaskList = [];
    }

    public static function initialTaskList(): void
    {
        $crontabTaskList = config('crontab', []);
        foreach ($crontabTaskList as $crontabTask) {
            if (class_exists($crontabTask)) {
                $taskObj = new $crontabTask;
                if ($taskObj instanceof CrontabTaskInterface) {
                    self::addTask($crontabTask, $taskObj);
                }
            }
        }
    }

}