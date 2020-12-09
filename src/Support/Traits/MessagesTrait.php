<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

trait MessagesTrait
{

    /** @var array */
    protected array $messages = [];

    /**
     * Given $key and $message to set message
     * @param mixed $key
     * @param mixed $message
     * @return static
     */
    public function setMessage(string $key, string $message): self
    {
        $this->messages[$key] = $message;
        return $this;
    }

    /**
     * Given $messages and set multiple messages
     * @param array $messages
     * @return static
     */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Given message from given $key
     * @param string $key
     * @return string
     */
    public function getMessage(string $key): string
    {
        return array_key_exists($key, $this->messages) ? $this->messages[$key] : $key;
    }

    /**
     * Get all $messages
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * clear all $messages
     */
    public function clearMessages(): void
    {
        $this->messages = [];
    }
}
