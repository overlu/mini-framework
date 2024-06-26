<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\Table;
use Mini\Crontab\CrontabTaskList;
use Swoole\Process;

class StatusCrontabCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
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
            ? $this->message('no crontab.')
            : Table::show($data, 'Mini Crontab List');

        return true;
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