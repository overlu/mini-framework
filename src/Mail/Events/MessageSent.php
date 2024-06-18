<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail\Events;

use Mini\Mail\SentMessage;
use RuntimeException;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * @property Email $message
 */
class MessageSent
{
    /**
     * The message that was sent.
     *
     * @var SentMessage
     */
    public SentMessage $sent;

    /**
     * The message data.
     *
     * @var array
     */
    public array $data;

    /**
     * Create a new event instance.
     *
     * @param SentMessage $message
     * @param array $data
     * @return void
     */
    public function __construct(SentMessage $message, array $data = [])
    {
        $this->sent = $message;
        $this->data = $data;
    }

    /**
     * Get the serializable representation of the object.
     *
     * @return array
     */
    public function __serialize(): array
    {
        $hasAttachments = collect($this->message->getAttachments())->isNotEmpty();

        return [
            'sent' => $this->sent,
            'data' => $hasAttachments ? base64_encode(serialize($this->data)) : $this->data,
            'hasAttachments' => $hasAttachments,
        ];
    }

    /**
     * Marshal the object from its serialized data.
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->sent = $data['sent'];

        $this->data = (($data['hasAttachments'] ?? false) === true)
            ? unserialize(base64_decode($data['data']))
            : $data['data'];
    }

    /**
     * Dynamically get the original message.
     * @param string $key
     * @return RawMessage
     */
    public function __get(string $key)
    {
        if ($key === 'message') {
            return $this->sent->getOriginalMessage();
        }

        throw new RuntimeException('Unable to access undefined property on ' . __CLASS__ . ': ' . $key);
    }
}
