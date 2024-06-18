<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail\Events;

use Symfony\Component\Mime\Email;

class MessageSending
{
    /**
     * The Symfony Email instance.
     *
     * @var Email
     */
    public Email $message;

    /**
     * The message data.
     *
     * @var array
     */
    public array $data;

    /**
     * Create a new event instance.
     *
     * @param Email $message
     * @param array $data
     * @return void
     */
    public function __construct(Email $message, array $data = [])
    {
        $this->data = $data;
        $this->message = $message;
    }
}
