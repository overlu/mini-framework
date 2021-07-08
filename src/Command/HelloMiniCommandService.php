<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use App\Models\Slip;
use Mini\Facades\Console;
use Mini\Support\Command;
use Swoole\Process;

class HelloMiniCommandService extends AbstractCommandService
{
    /**
     * @param Process $process
     * @return mixed|void
     */
    public function handle(Process $process)
    {
        $info = <<<EOL
 _   _ _____ _     _     ___       __  __ ___ _   _ ___ 
| | | | ____| |   | |   / _ \     |  \/  |_ _| \ | |_ _|
| |_| |  _| | |   | |  | | | |    | |\/| || ||  \| || | 
|  _  | |___| |___| |__| |_| |    | |  | || || |\  || | 
|_| |_|_____|_____|_____\___/     |_|  |_|___|_| \_|___|\n
EOL;
        Command::info($info);
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