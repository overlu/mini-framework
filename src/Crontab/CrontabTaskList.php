<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

use InvalidArgumentException;

/**
 * Class CrontabTaskList
 * @package Mini\Crontab
 */
class CrontabTaskList
{
    /**
     * @var AbstractCrontabTask[]
     */
    private static array $crontabTaskList = [];

    /**
     * Get all crontab tasks
     * @return AbstractCrontabTask[]
     */
    public static function getCrontabTaskList(): array
    {
        return self::$crontabTaskList;
    }

    /**
     * Get crontab task
     * @param string $name
     * @return AbstractCrontabTask|null
     */
    public static function getTask(string $name): ?AbstractCrontabTask
    {
        return self::$crontabTaskList[$name] ?? null;
    }

    /**
     * Add crontab task (not override)
     * @param string $name
     * @param AbstractCrontabTask $handle
     */
    public static function addTask(string $name, AbstractCrontabTask $handle): void
    {
        isset(self::$crontabTaskList[$name]) || self::$crontabTaskList[$name] = $handle;
    }

    /**
     * Set crontab task (override)
     * @param string $name
     * @param AbstractCrontabTask $handle
     */
    public static function setTask(string $name, AbstractCrontabTask $handle): void
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
                throw new InvalidArgumentException('Class ' . $crontabTask . ' not exists.');
            }
            $taskObj = new $crontabTask;
            if (!($taskObj instanceof AbstractCrontabTask)) {
                throw new InvalidArgumentException('Task ' . $crontabTask . ' should instanceof ' . AbstractCrontabTask::class);
            }
            self::addTask($crontabTask, $taskObj);
        }
    }
}