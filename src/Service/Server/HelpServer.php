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
        'start http' => 'start mini http server',
        'start ws' => 'start mini websocket server',
        'start mqtt' => 'start mini mqtt server',
//        'start main' => 'start mini http server',
        'start all' => 'start all mini server',
        '',
        "stop \t" => 'stop all mini server',
        'stop http' => 'stop mini http server',
        'stop ws ' => 'stop mini websocket server',
        'stop mqtt' => 'stop mini mqtt server',
//        'stop main' => 'stop mini http server',
        'stop all' => 'stop all mini server',
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
