<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Mail;

use DateInterval;
use DateTimeInterface;

interface MailQueue
{
    /**
     * Queue a new e-mail message for sending.
     *
     * @param array|string|Mailable $view
     * @param string|null $queue
     * @return mixed
     */
    public function queue(array|string|Mailable $view, string $queue = null): mixed;

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param DateInterval|DateTimeInterface|int $delay
     * @param array|string|Mailable $view
     * @param string|null $queue
     * @return mixed
     */
    public function later(DateInterval|DateTimeInterface|int $delay, array|string|Mailable $view, string $queue = null): mixed;
}
