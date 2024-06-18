<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail\Mailables;

use Mini\Support\Str;
use Mini\Support\Traits\Conditionable;

class Headers
{
    use Conditionable;

    /**
     * The message's message ID.
     *
     * @var string|null
     */
    public ?string $messageId = null;

    /**
     * The message IDs that are referenced by the message.
     *
     * @var array
     */
    public array $references;

    /**
     * The message's text headers.
     *
     * @var array
     */
    public array $text;

    /**
     * Create a new instance of headers for a message.
     *
     * @param string|null $messageId
     * @param array $references
     * @param array $text
     * @return void
     *
     * @named-arguments-supported
     */
    public function __construct(string $messageId = null, array $references = [], array $text = [])
    {
        $this->messageId = $messageId;
        $this->references = $references;
        $this->text = $text;
    }

    /**
     * Set the message ID.
     *
     * @param string $messageId
     * @return $this
     */
    public function messageId(string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Set the message IDs referenced by this message.
     *
     * @param array $references
     * @return $this
     */
    public function references(array $references): self
    {
        $this->references = array_merge($this->references, $references);

        return $this;
    }

    /**
     * Set the headers for this message.
     *
     * @param array $text
     * @return $this
     */
    public function text(array $text): self
    {
        $this->text = array_merge($this->text, $text);

        return $this;
    }

    /**
     * Get the references header as a string.
     *
     * @return string
     */
    public function referencesString(): string
    {
        return collect($this->references)->map(function ($messageId) {
            return Str::finish(Str::start($messageId, '<'), '>');
        })->implode(' ');
    }
}
