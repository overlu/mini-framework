<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail\Transport;

use Mini\Support\Collection;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class ArrayTransport implements TransportInterface
{
    /**
     * The collection of Symfony Messages.
     *
     * @var Collection
     */
    protected Collection $messages;

    /**
     * Create a new array transport instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = new Collection;
    }

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        return $this->messages[] = new SentMessage($message, $envelope ?? Envelope::create($message));
    }

    /**
     * Retrieve the collection of messages.
     *
     * @return Collection
     */
    public function messages(): Collection
    {
        return $this->messages;
    }

    /**
     * Clear all of the messages from the local collection.
     *
     * @return Collection
     */
    public function flush(): Collection
    {
        return $this->messages = new Collection;
    }

    /**
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'array';
    }
}
