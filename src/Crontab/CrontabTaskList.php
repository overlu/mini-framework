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
     * Get all crontab tasks
     * @return CrontabTaskInterface[]
     */
    public static function getCrontabTaskList(): array
    {
        return self::$crontabTaskList;
    }

    /**
     * Get crontab task
     * @param string $name
     * @return CrontabTaskInterface|null
     */
    public static function getTask(string $name): ?CrontabTaskInterface
    {
        return self::$crontabTaskList[$name] ?? null;
    }

    /**
     * Add crontab task (not override)
     * @param string $name
     * @param CrontabTaskInterface $handle
     */
    public static function addTask(string $name, CrontabTaskInterface $handle): void
    {
        isset(self::$crontabTaskList[$name]) || self::$crontabTaskList[$name] = $handle;
    }

    /**
     * Set crontab task (override)
     * @param string $name
     * @param CrontabTaskInterface $handle
     */
    public static function setTask(string $name, CrontabTaskInterface $handle): void
    {
        self::$crontabTaskList[$name] = $handle;
    }

    /**
     * Remove crontab task
     * @param string $name
     */
    public static function removeTask(string $name): void
    {
        if (self::$crontabTaskList[$name]) {
            unset(self::$crontabTaskList[$name]);
        }
    }

    /**
     * Truncate crontab tasks
     */
    public static function truncate(): void
    {
        self::$crontabTaskList = [];
    }

    /**
     * Initial crontab tasks
     */
    public static function initialTaskList(): void
    {
        $crontabTaskList = config('crontab.crontab_task_list', []);
        foreach ($crontabTaskList as $crontabTask) {
            if (!class_exists($crontabTask)) {
                throw new \InvalidArgumentException('Class ' . $crontabTask . ' not exists.');
            }
            $taskObj = new $crontabTask;
            if (!($taskObj instanceof CrontabTaskInterface)) {
                throw new \InvalidArgumentException('Task ' . $crontabTask . ' should instanceof ' . CrontabTaskInterface::class);
            }
            self::addTask($crontabTask, $taskObj);
        }
    }
}