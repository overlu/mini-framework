<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Command\BaseCommandService;
use Mini\Console\Table;
use Mini\Crontab\Crontab;
use Mini\Crontab\CrontabTaskList;
use Mini\Support\Command;
use Swoole\Timer;

class StatusCrontabCommandService extends BaseCommandService
{
    public string $command = 'crontab:status';

    public string $description = 'view crontab task status';

    public function run()
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
}