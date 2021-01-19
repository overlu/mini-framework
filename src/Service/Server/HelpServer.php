<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Support\Command;

class HelpServer
{
    protected string $delimiter = '-------------------------------------------';
    protected array $commands = [
        '',
        "start \t" => 'start mini http server',
        'start all' => 'start mini all server',
        'start {type}' => 'start mini custom server',
        '',
        "stop \t" => 'stop all mini server',
        'stop all' => 'stop mini all server',
        'stop {type}' => 'stop mini custom server',
    ];

    public function __construct()
    {
        Command::info("\033[32m" . 'mini commands' . "\033[0m \t" . 'mini commands description');
        Command::line($this->delimiter);
        foreach ($this->commands as $command => $description) {
            Command::line(
                trim($description)
                    ? "\033[32m{$command}\033[0m\t$description"
                    : '');
        }
        Command::line();
        exit(1);
    }
}
