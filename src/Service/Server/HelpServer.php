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
        "start|reload|stop \t" => 'start|reload|stop mini http server',
        'start|reload|stop all' => 'start|reload|stop mini all server',
        'start|reload|stop {type}' => 'start|reload|stop mini {type} server',
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
