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

    /**
     * Queue a new e-mail message for sending.
     *
     * @param array|string|Mailable $view
     * @param Closure|string|null $callable
     * @return void
     */
    public function queue(array|string|Mailable $view, Closure|string $callable = null): void;

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     * @param array|string|Mailable $view
     * @param int $delay
     * @param Closure|string|null $callable
     * @return void
     */
    public function later(array|string|Mailable $view, int $delay, Closure|string $callable = null): void;

    /**
     * Queue a new e-mail message for sending at $dateTime.
     * @param array|string|Mailable $view
     * @param \DateTimeInterface $dateTime
     * @param Closure|string|null $callable
     * @return void
     */
    public function laterOn(array|string|Mailable $view, \DateTimeInterface $dateTime, Closure|string $callable = null): void;
}
