<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use JetBrains\PhpStorm\NoReturn;
use Mini\Support\Command;

class HelpServer
{
    protected array $commands = [
        "start|reload|stop \t" => 'start|reload|stop mini http server',
        'start|reload|stop {type}' => 'start|reload|stop mini {type} server',
        "start|reload|stop all \t" => 'start|reload|stop mini all server',
    ];
    protected string $delimiter = '-------------------------------------------';

    #[NoReturn]
    public function __construct()
    {
        Command::line();
        Command::info("\033[32m" . 'mini commands' . "\033[0m \t\t\t" . 'mini commands description');
        Command::message($this->delimiter);
        foreach ($this->commands as $command => $description) {
            Command::message(
                trim($description)
                    ? "\033[32m{$command}\033[0m\t$description"
                    : '');
        }
        Command::line();
        exit(1);
    }
}
