<?php
/**
 * This file is part of Zhuge.
 * @auth lupeng
 * @date 2024/9/24 上午10:39
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\App;
use Swoole\Process;

class CommandExecute
{
    private string $command;

    private AbstractCommandService $commandService;

    private bool $enableCoroutine;

    private ?Process $process;

    private App $app;

    public function __construct(string $command, AbstractCommandService $commandService, bool $enableCoroutine = false)
    {
        $this->command = $command;
        $this->commandService = $commandService;
        $this->enableCoroutine = $enableCoroutine;
    }

    public function setApp(App $app): self
    {
        $this->app = $app;
        return $this;
    }

    public function setProcess(?Process $process): self
    {
        $this->process = $process;
        return $this;
    }

    public function getProcess(): ?Process
    {
        return $this->process;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getCommandService(): AbstractCommandService
    {
        return $this->commandService;
    }

    public function isEnableCoroutine(): bool
    {
        return $this->enableCoroutine;
    }

    public function __invoke()
    {
        if ($this->process && $this->enableCoroutine) {
            $this->process->set(['enable_coroutine' => true]);
        }
        $this->commandService->setApp($this->app)->handle($this->process);
    }
}