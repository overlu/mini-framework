<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail;

use Mini\Contracts\Mail\Attachable;
use Mini\Support\Str;
use Mini\Support\Traits\ForwardsCalls;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

/**
 * @mixin Email
 */
class Message
{
    use ForwardsCalls;

    /**
     * The Symfony Email instance.
     *
     * @var Email
     */
    protected Email $message;

    /**
     * CIDs of files embedded in the message.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @var array
     */
    protected array $embeddedFiles = [];

    /**
     * Create a new message instance.
     *
     * @param Email $message
     * @return void
     */
    public function __construct(Email $message)
    {
        $this->message = $message;
    }

    /**
     * Add a "from" address to the message.
     *
     * @param array|string $address
     * @param string|null $name
     * @return $this
     */
    public function from(array|string $address, string $name = ''): self
    {
        is_array($address)
            ? $this->message->from(...$address)
            : $this->message->from(new Address($address, $name));

        return $this;
    }

    /**
     * Set the "sender" of the message.
     *
     * @param array|string $address
     * @param string|null $name
     * @return $this
     */
    public function sender(array|string $address, string $name = ''): self
    {
        is_array($address)
            ? $this->message->sender(...$address)
            : $this->message->sender(new Address($address, $name));

        return $this;
    }

    /**
     * Set the "return path" of the message.
     *
     * @param string $address
     * @return $this
     */
    public function returnPath(string $address): self
    {
        $this->message->returnPath($address);

        return $this;
    }

    /**
     * Add a recipient to the message.
     *
     * @param array|string $address
     * @param string|null $name
     * @param bool $override
     * @return $this
     */
    public function to(array|string $address, string $name = '', bool $override = false): self
    {
        if ($override) {
            is_array($address)
                ? $this->message->to(...$address)
                : $this->message->to(new Address($address, $name));

            return $this;
        }

        return $this->addAddresses($address, $name, 'To');
    }

    /**
     * Remove all "to" addresses from the message.
     *
     * @return $this
     */
    public function forgetTo(): self
    {
        if ($header = $this->message->getHeaders()->get('To')) {
            $this->addAddressDebugHeader('X-To', $this->message->getTo());

            $header->setAddresses([]);
        }

        return $this;
    }

    /**
     * Add a carbon copy to the message.
     *
     * @param array|string $address
     * @param string|null $name
     * @param bool $override
     * @return $this
     */
    public function cc(array|string $address, string $name = '', bool $override = false): self
    {
        if ($override) {
            is_array($address)
                ? $this->message->cc(...$address)
                : $this->message->cc(new Address($address, $name));

            return $this;
        }

        return $this->addAddresses($address, $name, 'Cc');
    }

    /**
     * Remove all carbon copy addresses from the message.
     *
     * @return $this
     */
    public function forgetCc(): self
    {
        if ($header = $this->message->getHeaders()->get('Cc')) {
            $this->addAddressDebugHeader('X-Cc', $this->message->getCC());

            $header->setAddresses([]);
        }

        return $this;
    }

    /**
     * Add a blind carbon copy to the message.
     *
     * @param array|string $address
     * @param string|null $name
     * @param bool $override
     * @return $this
     */
    public function bcc(array|string $address, string $name = '', bool $override = false): self
    {
        if ($override) {
            is_array($address)
                ? $this->message->bcc(...$address)
                : $this->message->bcc(new Address($address, $name));

            return $this;
        }

        return $this->addAddresses($address, $name, 'Bcc');
    }

    /**
     * Remove all of the blind carbon copy addresses from the message.
     *
     * @return $this
     */
    public function forgetBcc(): self
    {
        if ($header = $this->message->getHeaders()->get('Bcc')) {
            $this->addAddressDebugHeader('X-Bcc', $this->message->getBcc());

            $header->setAddresses([]);
        }

        return $this;
    }

    /**
     * Add a "reply to" address to the message.
     *
     * @param array|string $address
     * @param string|null $name
     * @return $this
     */
    public function replyTo(array|string $address, string $name = ''): self
    {
        return $this->addAddresses($address, $name, 'ReplyTo');
    }

    /**
     * Add a recipient to the message.
     *
     * @param array|string $address
     * @param string $name
     * @param string $type
     * @return $this
     */
    protected function addAddresses(array|string $address, string $name, string $type): self
    {
        if (is_array($address)) {
            $type = lcfirst($type);

            $addresses = collect($address)->map(function ($address, $key) {
                if (is_string($key) && is_string($address)) {
                    return new Address($key, $address);
                }

                if (is_array($address)) {
                    return new Address($address['email'] ?? $address['address'], $address['name'] ?? null);
                }

                if (is_null($address)) {
                    return new Address($key);
                }

                return $address;
            })->all();

            $this->message->{"{$type}"}(...$addresses);
        } else {
            $this->message->{"add{$type}"}(new Address($address, (string)$name));
        }

        return $this;
    }

    /**
     * Add an address debug header for a list of recipients.
     *
     * @param string $header
     * @param Address[] $addresses
     * @return $this
     */
    protected function addAddressDebugHeader(string $header, array $addresses): self
    {
        $this->message->getHeaders()->addTextHeader(
            $header,
            implode(', ', array_map(fn($a) => $a->toString(), $addresses)),
        );

        return $this;
    }

    /**
     * Set the subject of the message.
     *
     * @param string $subject
     * @return $this
     */
    public function subject(string $subject): self
    {
        $this->message->subject($subject);

        return $this;
    }

    /**
     * Set the message priority level.
     *
     * @param int $level
     * @return $this
     */
    public function priority(int $level): self
    {
        $this->message->priority($level);

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param string|Attachable|Attachment $file
     * @param array $options
     * @return $this
     */
    public function attach(string|Attachment|Attachable $file, array $options = []): self
    {
        if ($file instanceof Attachable) {
            $file = $file->toMailAttachment();
        }

        if ($file instanceof Attachment) {
            return $file->attachTo($this);
        }

        $this->message->attachFromPath($file, $options['as'] ?? null, $options['mime'] ?? null);

        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param string $data
     * @param string $name
     * @param array $options
     * @return $this
     */
    public function attachData(string $data, string $name, array $options = []): self
    {
        $this->message->attach($data, $name, $options['mime'] ?? null);

        return $this;
    }

    /**
     * Embed a file in the message and get the CID.
     *
     * @param string|Attachable|Attachment $file
     * @return string
     */
    public function embed(string|Attachment|Attachable $file): string
    {
        if ($file instanceof Attachable) {
            $file = $file->toMailAttachment();
        }

        if ($file instanceof Attachment) {
            return $file->attachWith(
                function ($path) use ($file) {
                    $cid = $file->as ?? Str::random();

                    $this->message->addPart(
                        (new DataPart(new File($path), $cid, $file->mime))->asInline()
                    );

                    return "cid:{$cid}";
                },
                function ($data) use ($file) {
                    $this->message->addPart(
                        (new DataPart($data(), $file->as, $file->mime))->asInline()
                    );

                    return "cid:{$file->as}";
                }
            );
        }

        $cid = Str::random(10);

        $this->message->addPart(
            (new DataPart(new File($file), $cid))->asInline()
        );

        return "cid:$cid";
    }

    /**
     * Embed in-memory data in the message and get the CID.
     *
     * @param string $data
     * @param string $name
     * @param string|null $contentType
     * @return string
     */
    public function embedData(string $data, string $name, string $contentType = null): string
    {
        $this->message->addPart(
            (new DataPart($data, $name, $contentType))->asInline()
        );

        return "cid:$name";
    }

    /**
     * Get the underlying Symfony Email instance.
     *
     * @return Email
     */
    public function getSymfonyMessage(): Email
    {
        return $this->message;
    }

    /**
     * Dynamically pass missing methods to the Symfony instance.
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
