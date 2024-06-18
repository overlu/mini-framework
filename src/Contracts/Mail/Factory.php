<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Mail;

interface Factory
{
    /**
     * Get a mailer instance by name.
     *
     * @param string|null $name
     * @return Mailer
     */
    public function mailer(string $name = null): Mailer;
}
