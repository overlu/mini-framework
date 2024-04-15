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

    protected string $expression = '* * * * * *';

    private const SUNDAY = 0;
    private const MONDAY = 1;
    private const TUESDAY = 2;
    private const WEDNESDAY = 3;
    private const THURSDAY = 4;
    private const FRIDAY = 5;
    private const SATURDAY = 6;

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