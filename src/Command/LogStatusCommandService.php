<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\Table;
use SeasLog;
use Swoole\Process;

/**
 * Class LogStatusCommandService
 * @package Mini\Command
 */
class LogStatusCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        $status = SeasLog::analyzerCount();
        $total = 0;
        foreach ($status as $key => $value) {
            $status[$key] = (string)$value;
            $total += $value;
        }
        $status['TOTAL'] = (string)$total;
        Table::show([$status], ' ');
        return true;
    }

    public function getCommand(): string
    {
        return 'log:status';
    }

    public function getCommandDescription(): string
    {
        return 'View Log Status';
    }
}