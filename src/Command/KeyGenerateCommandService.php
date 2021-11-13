<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Encryption\Encrypter;
use Mini\Support\Command;
use Swoole\Process;

class KeyGenerateCommandService extends AbstractCommandService
{
    use ConfirmableTrait;

    /**
     * @param Process $process
     * @return void
     * @throws \Exception
     */
    public function handle(Process $process): void
    {
        $key = $this->generateRandomKey();

        if ($this->getOpt('show')) {
            $this->comment($key);
            return;
        }

        // Next, we will replace the application key in the environment file so it is
        // automatically setup for this developer. This key gets generated using a
        // secure random byte generator and is later base64 encoded for storage.
        if (!$this->setKeyInEnvironmentFile($key)) {
            return;
        }

        Command::info('Application key set successfully.');
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     * @throws \Exception
     */
    protected function generateRandomKey(): string
    {
        return 'base64:' . base64_encode(
                Encrypter::generateKey(config('app.cipher'))
            );
    }

    /**
     * Set the application key in the environment file.
     *
     * @param string $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile(string $key): bool
    {
        $currentKey = config('app.key');

        if ($currentKey !== '' && (!$this->confirmToProceed())) {
            return false;
        }

        $this->writeNewEnvironmentFileWith($key);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param string $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith(string $key): void
    {
        env([
            'APP_KEY' => $key
        ]);
    }

    public function getCommand(): string
    {
        return 'key:generate';
    }

    public function getCommandDescription(): string
    {
        return 'Set the application key.
                   <blue>{--show : Display the key instead of modifying files}
                   {--force : Force the operation to run when in production}</blue>';
    }
}