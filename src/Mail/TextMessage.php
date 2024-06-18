<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail;

use Mini\Contracts\Mail\Attachable;
use Mini\Support\Traits\ForwardsCalls;

/**
 * @mixin Message
 */
class TextMessage
{
    use ForwardsCalls;

    /**
     * The underlying message instance.
     *
     * @var Message
     */
    protected Message $message;

    /**
     * Create a new text message instance.
     *
     * @param Message $message
     * @return void
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Embed a file in the message and get the CID.
     *
     * @param string|Attachable|Attachment $file
     * @return string
     */
    public function embed($file)
    {
        return '';
    }

    /**
     * Embed in-memory data in the message and get the CID.
     *
     * @param string|resource $data
     * @param string $name
     * @param string|null $contentType
     * @return string
     */
    public function embedData($data, $name, $contentType = null)
    {
        return '';
    }

    /**
     * Dynamically pass missing methods to the underlying message instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardDecoratedCallTo($this->message, $method, $parameters);
    }
}
