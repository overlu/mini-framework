<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail\Transport;

use Mini\Facades\Logger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class LogTransport implements TransportInterface
{
    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $string = $message->toString();

        if (str_contains($string, 'Content-Transfer-Encoding: quoted-printable')) {
            $string = quoted_printable_decode($string);
        }

        Logger::debug($string, [], 'mail');

        return new SentMessage($message, $envelope ?? Envelope::create($message));
    }

    /**
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'log';
    }
}
