<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;


use Closure;

trait ConfirmableTrait
{
    /**
     * Confirm before proceeding with the action.
     *
     * This method only asks for confirmation in production.
     *
     * @param string $warning
     * @param Closure|bool|null $callback
     * @return bool
     */
    public function confirmToProceed(string $warning = 'Application In Production!', $callback = null): bool
    {
        $callback = is_null($callback) ? $this->getDefaultConfirmCallback() : $callback;

        $shouldConfirm = value($callback);

        if ($shouldConfirm) {
            if ($this->hasOption('force') && $this->option('force')) {
                return true;
            }

            $this->alert($warning);

            $confirmed = $this->confirm('Do you really wish to run this command?');

            if (!$confirmed) {
                $this->comment('Command Canceled!');

                return false;
            }
        }

        return true;
    }

    /**
     * Get the default confirmation callback.
     *
     * @return Closure
     */
    protected function getDefaultConfirmCallback(): Closure
    {
        return static function () {
            return app()->environment() === 'production';
        };
    }
}