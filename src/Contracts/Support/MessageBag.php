<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Support;

interface MessageBag
{
    /**
     * Get the keys present in the message bag.
     */
    public function keys(): array;

    /**
     * Add a message to the bag.
     * @param string $key
     * @param string $message
     * @return MessageBag
     */
    public function add(string $key, string $message): MessageBag;

    /**
     * Merge a new array of messages into the bag.
     *
     * @param array|MessageProvider $messages
     * @return $this
     */
    public function merge($messages);

    /**
     * Determine if messages exist for a given key.
     *
     * @param array|string $key
     * @return bool
     */
    public function has($key): bool;

    /**
     * Get the first message from the bag for a given key.
     * @param string|null $key
     * @param string|null $format
     * @return string
     */
    public function first(?string $key = null, ?string $format = null): string;

    /**
     * Get all of the messages from the bag for a given key.
     * @param string $key
     * @param string|null $format
     * @return array
     */
    public function get(string $key, ?string $format = null): array;

    /**
     * Get all of the messages for every key in the bag.
     * @param string|null $format
     * @return array
     */
    public function all(?string $format = null): array;

    /**
     * Get the raw messages in the container.
     */
    public function getMessages(): array;

    /**
     * Get the default message format.
     */
    public function getFormat(): string;

    /**
     * Set the default message format.
     *
     * @param string $format
     * @return $this
     */
    public function setFormat(string $format = ':message');

    /**
     * Determine if the message bag has any messages.
     */
    public function isEmpty(): bool;

    /**
     * Determine if the message bag has any messages.
     */
    public function isNotEmpty(): bool;

    /**
     * Get the number of messages in the container.
     */
    public function count(): int;
}
