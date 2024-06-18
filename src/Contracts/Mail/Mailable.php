<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Mail;

use DateInterval;
use DateTimeInterface;
use Mini\Contracts\Queue\Factory as Queue;
use Mini\Mail\SentMessage;

interface Mailable
{
    /**
     * Send the message using the given mailer.
     *
     * @param Factory|Mailer $mailer
     * @return SentMessage|null
     */
    public function send(Factory|Mailer $mailer): ?SentMessage;

    /**
     * Set the recipients of the message.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return self
     */
    public function cc(object|array|string $address, string $name = null): self;

    /**
     * Set the recipients of the message.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return $this
     */
    public function bcc(object|array|string $address, string $name = null): self;

    /**
     * Set the recipients of the message.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return $this
     */
    public function to(object|array|string $address, string $name = null): self;

    /**
     * Set the locale of the message.
     *
     * @param string $locale
     * @return $this
     */
    public function locale(string $locale): self;

    /**
     * Set the name of the mailer that should be used to send the message.
     *
     * @param string $mailer
     * @return $this
     */
    public function mailer(string $mailer): self;
}
