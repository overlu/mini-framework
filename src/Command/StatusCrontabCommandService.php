<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\Table;
use Mini\Crontab\CrontabTaskList;
use Mini\Support\Command;
use Swoole\Process;

class StatusCrontabCommandService extends AbstractCommandService
{
    public function handle(Process $process): void
    {
        CrontabTaskList::initialTaskList();
        $crontabTaskList = CrontabTaskList::getCrontabTaskList();
        $data = [];
        foreach ($crontabTaskList as $crontabTask) {
            $data[] = [
                'class' => get_class($crontabTask),
                'name' => $crontabTask->name(),
                'rule' => $crontabTask->rule(),
                'description' => $crontabTask->description(),
                'status' => $crontabTask->status() ? "\e[0;32mon\e[0m" : "\e[0;31moff\e[0m"
            ];
        }
        empty($data)
            ? Command::line('no crontab.')
            : Table::show($data, 'Mini Crontab List');
    }

    public function getCommand(): string
    {
        return 'crontab:status';
    }

    public function getCommandDescription(): string
    {
        return 'view crontab task status';
    }
}