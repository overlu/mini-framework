<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Mail;

use Closure;
use Mini\Mail\PendingMail;
use Mini\Mail\SentMessage;

interface Mailer
{
    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param mixed $users
     * @return PendingMail
     */
    public function to(mixed $users): PendingMail;

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param mixed $users
     * @return PendingMail
     */
    public function bcc(mixed $users): PendingMail;

    /**
     * Send a new message with only a raw text part.
     *
     * @param string $text
     * @param mixed $callback
     * @return SentMessage|null
     */
    public function raw(string $text, mixed $callback): ?SentMessage;

    /**
     * Send a new message using a view.
     *
     * @param array|string|Mailable $view
     * @param array $data
     * @param Closure|string|null $callback
     * @return SentMessage|null
     */
    public function send(array|string|Mailable $view, array $data = [], Closure|string $callback = null): ?SentMessage;
}
