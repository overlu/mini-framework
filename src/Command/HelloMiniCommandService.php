<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Swoole\Process;

class HelloMiniCommandService extends AbstractCommandService
{
    /**
     * @param Process $process
     * @return void
     */
    public function handle(Process $process): void
    {
        $info = <<<EOL
 _   _ _____ _     _     ___       __  __ ___ _   _ ___ 
| | | | ____| |   | |   / _ \     |  \/  |_ _| \ | |_ _|
| |_| |  _| | |   | |  | | | |    | |\/| || ||  \| || | 
|  _  | |___| |___| |__| |_| |    | |  | || || |\  || | 
|_| |_|_____|_____|_____\___/     |_|  |_|___|_| \_|___|\n
EOL;
        $this->info($info);
    }

    public function getCommand(): string
    {
        return 'hello:mini';
    }

    public function getCommandDescription(): string
    {
        return 'print hello app.';
    }
}