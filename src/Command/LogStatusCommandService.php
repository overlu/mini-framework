<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\Table;

/**
 * Class LogStatusCommandService
 * @package Mini\Command
 */
class LogStatusCommandService extends BaseCommandService
{
    public static string $command = 'log:status';

    public static string $description = 'View Log Status';

    public function run()
    {
        $status = \SeasLog::analyzerCount();
        $total = 0;
        foreach ($status as $key => $value) {
            $status[$key] = (string)$value;
            $total += $value;
        }
        $status['TOTAL'] = (string)$total;
        Table::show([$status], ' ');
    }
}