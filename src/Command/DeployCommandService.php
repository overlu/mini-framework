<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\Cli;
use Mini\Support\Command;
use Swoole\Process;

class DeployCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        $env = $this->getFirstArg();
        if (empty($env)) {
            $this->error('Missing environment');
            return false;
        }
        $shell = 'vendor/bin/dep deploy env=' . $env;

        if ($this->getOpt('root')) {
            $shell .= ' -o become=root';
        }

        $this->output($shell, $process);

        return true;
    }

    public function getCommand(): string
    {
        return 'deploy';
    }

    public function getCommandDescription(): string
    {
        return 'deploy mini server.
                   <blue>{dev/production/...} : The server environment.}
                   {--root : Use sudo deploy the mini server}</blue>';
    }
}