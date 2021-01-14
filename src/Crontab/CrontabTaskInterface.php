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

    public function getCrontabName(): string;

    public function getCrontabDescription(): string;

    public function getRule(): string;
}