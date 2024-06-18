<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail;

use Mini\Contracts\Mail\Mailable as MailableContract;
use Mini\Contracts\Mail\Mailer as MailerContract;
use Mini\Contracts\Translation\HasLocalePreference;
use Mini\Support\Traits\Conditionable;

class PendingMail
{
    use Conditionable;

    /**
     * The mailer instance.
     *
     * @var MailerContract
     */
    protected MailerContract $mailer;

    /**
     * The locale of the message.
     *
     * @var string
     */
    protected string $locale;

    /**
     * The "to" recipients of the message.
     *
     * @var array
     */
    protected array $to = [];

    /**
     * The "cc" recipients of the message.
     *
     * @var array
     */
    protected array $cc = [];

    /**
     * The "bcc" recipients of the message.
     *
     * @var array
     */
    protected array $bcc = [];

    /**
     * Create a new mailable mailer instance.
     *
     * @param MailerContract $mailer
     * @return void
     */
    public function __construct(MailerContract $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Set the locale of the message.
     *
     * @param string $locale
     * @return $this
     */
    public function locale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * @param mixed $users
     * @return $this
     */
    public function to(mixed $users): self
    {
        $this->to = $users;

        if (!$this->locale && $users instanceof HasLocalePreference) {
            $this->locale($users->preferredLocale());
        }

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * @param mixed $users
     * @return $this
     */
    public function cc(mixed $users): self
    {
        $this->cc = $users;

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * @param mixed $users
     * @return $this
     */
    public function bcc(mixed $users): self
    {
        $this->bcc = $users;

        return $this;
    }

    /**
     * Send a new mailable message instance.
     *
     * @param MailableContract $mailable
     * @return SentMessage|null
     */
    public function send(MailableContract $mailable): ?SentMessage
    {
        return $this->mailer->send($this->fill($mailable));
    }

    /**
     * Populate the mailable with the addresses.
     *
     * @param MailableContract $mailable
     * @return Mailable
     */
    protected function fill(MailableContract $mailable): Mailable
    {
        return tap($mailable->to($this->to)
            ->cc($this->cc)
            ->bcc($this->bcc), function (MailableContract $mailable) {
            if ($this->locale) {
                $mailable->locale($this->locale);
            }
        });
    }
}
