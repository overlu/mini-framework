<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

abstract class AbstractCrontabTask implements CrontabTaskInterface
{
    use ManagesFrequencies;

    public string $expression = '* * * * * *';

    public const SUNDAY = 0;
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;

    abstract public function handle();

    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function rule(): string;

    public function status(): bool
    {
        return true;
    }

//    public function success($result)
//    {
//        //
//    }
//
//    public function fail(\Exception $exception)
//    {
//        //
//    }
}