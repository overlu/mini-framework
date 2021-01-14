<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

/**
 * Interface CrontabTaskInterface
 * @package Mini\Crontab
 */
interface CrontabTaskInterface
{
    public function handle();

    public function name(): string;

    public function description(): string;

    /**
     * format: * * * * * / * * * * * *
     * @return string
     */
    public function rule(): string;

    /**
     * enable: true, disable: false
     * @return bool
     */
    public function status(): bool;
}