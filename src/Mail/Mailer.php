<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail;

use Closure;
use Mini\Contracts\Events\Dispatcher;
use Mini\Contracts\Mail\Mailable as MailableContract;
use Mini\Contracts\Mail\Mailer as MailerContract;
use Mini\Contracts\Support\Htmlable;
use Mini\Contracts\View\Factory;
use Mini\Mail\Events\MessageSending;
use Mini\Mail\Events\MessageSent;
use Mini\Mail\Mailables\Address;
use Mini\Support\HtmlString;
use Mini\Support\Traits\Macroable;
use InvalidArgumentException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class Mailer implements MailerContract
{
    use Macroable;

    /**
     * The name that is configured for the mailer.
     *
     * @var string
     */
    protected string $name;

    /**
     * The view factory instance.
     *
     * @var Factory
     */
    protected Factory $views;

    /**
     * The Symfony Transport instance.
     *
     * @var TransportInterface
     */
    protected TransportInterface $transport;

    /**
     * The event dispatcher instance.
     *
     * @var Dispatcher|null
     */
    protected ?Dispatcher $events = null;

    /**
     * The global from address and name.
     *
     * @var array
     */
    protected array $from;

    /**
     * The global reply-to address and name.
     *
     * @var array
     */
    protected array $replyTo;

    /**
     * The global return path address.
     *
     * @var array
     */
    protected array $returnPath;

    /**
     * The global to address and name.
     *
     * @var array
     */
    protected array $to;

    /**
     * Create a new Mailer instance.
     *
     * @param string $name
     * @param Factory $views
     * @param TransportInterface $transport
     * @param Dispatcher|null $events
     * @return void
     */
    public function __construct(string $name, Factory $views, TransportInterface $transport, Dispatcher $events = null)
    {
        $this->name = $name;
        $this->views = $views;
        $this->events = $events;
        $this->transport = $transport;
    }

    /**
     * Set the global from address and name.
     *
     * @param string $address
     * @param string|null $name
     * @return void
     */
    public function alwaysFrom(string $address, string $name = null): void
    {
        $this->from = compact('address', 'name');
    }

    /**
     * Set the global reply-to address and name.
     *
     * @param string $address
     * @param string|null $name
     * @return void
     */
    public function alwaysReplyTo(string $address, string $name = null): void
    {
        $this->replyTo = compact('address', 'name');
    }

    /**
     * Set the global return path address.
     *
     * @param string $address
     * @return void
     */
    public function alwaysReturnPath(string $address): void
    {
        $this->returnPath = compact('address');
    }

    /**
     * Set the global to address and name.
     *
     * @param string $address
     * @param string|null $name
     * @return void
     */
    public function alwaysTo(string $address, string $name = null): void
    {
        $this->to = compact('address', 'name');
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param mixed $users
     * @param string|null $name
     * @return PendingMail
     */
    public function to(mixed $users, string $name = null): PendingMail
    {
        if (!is_null($name) && is_string($users)) {
            $users = new Address($users, $name);
        }

        return (new PendingMail($this))->to($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param mixed $users
     * @param string|null $name
     * @return PendingMail
     */
    public function cc(mixed $users, string $name = null): PendingMail
    {
        if (!is_null($name) && is_string($users)) {
            $users = new Address($users, $name);
        }

        return (new PendingMail($this))->cc($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param mixed $users
     * @param string|null $name
     * @return PendingMail
     */
    public function bcc(mixed $users, string $name = null): PendingMail
    {
        if (!is_null($name) && is_string($users)) {
            $users = new Address($users, $name);
        }

        return (new PendingMail($this))->bcc($users);
    }

    /**
     * Send a new message with only an HTML part.
     *
     * @param string $html
     * @param mixed $callback
     * @return SentMessage|null
     */
    public function html(string $html, mixed $callback): ?SentMessage
    {
        return $this->send(['html' => new HtmlString($html)], [], $callback);
    }

    /**
     * Send a new message with only a raw text part.
     *
     * @param string $text
     * @param mixed $callback
     * @return SentMessage|null
     */
    public function raw(string $text, mixed $callback): ?SentMessage
    {
        return $this->send(['raw' => $text], [], $callback);
    }

    /**
     * Send a new message with only a plain part.
     *
     * @param string $view
     * @param array $data
     * @param mixed $callback
     * @return SentMessage|null
     */
    public function plain(string $view, array $data, mixed $callback): ?SentMessage
    {
        return $this->send(['text' => $view], $data, $callback);
    }

    /**
     * Render the given message as a view.
     *
     * @param array|string $view
     * @param array $data
     * @return string
     */
    public function render(array|string $view, array $data = []): string
    {
        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        [$view, $plain, $raw] = $this->parseView($view);

        $data['message'] = $this->createMessage();

        return $this->replaceEmbeddedAttachments(
            $this->renderView($view ?: $plain, $data),
            $data['message']->getSymfonyMessage()->getAttachments()
        );
    }

    /**
     * Replace the embedded image attachments with raw, inline image data for browser rendering.
     *
     * @param string $renderedView
     * @param array $attachments
     * @return string
     */
    protected function replaceEmbeddedAttachments(string $renderedView, array $attachments): string
    {
        if (preg_match_all('/<img.+?src=[\'"]cid:([^\'"]+)[\'"].*?>/i', $renderedView, $matches)) {
            foreach (array_unique($matches[1]) as $image) {
                foreach ($attachments as $attachment) {
                    if ($attachment->getFilename() === $image) {
                        $renderedView = str_replace(
                            'cid:' . $image,
                            'data:' . $attachment->getContentType() . ';base64,' . $attachment->bodyToString(),
                            $renderedView
                        );

                        break;
                    }
                }
            }
        }

        return $renderedView;
    }

    /**
     * Send a new message using a view.
     *
     * @param array|string|MailableContract $view
     * @param array $data
     * @param \Closure|string|null $callback
     * @return SentMessage|null
     */
    public function send(array|string|MailableContract $view, array $data = [], $callback = null): ?SentMessage
    {
        if ($view instanceof MailableContract) {
            return $this->sendMailable($view);
        }

        $data['mailer'] = $this->name;

        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        [$view, $plain, $raw] = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        // Once we have retrieved the view content for the e-mail we will set the body
        // of this message using the HTML type, which will provide a simple wrapper
        // to creating view based emails that are able to receive arrays of data.
        if (!is_null($callback)) {
            $callback($message);
        }

        $this->addContent($message, $view, $plain, $raw, $data);

        // If a global "to" address has been set, we will set that address on the mail
        // message. This is primarily useful during local development in which each
        // message should be delivered into a single mail address for inspection.
        if (isset($this->to['address'])) {
            $this->setGlobalToAndRemoveCcAndBcc($message);
        }

        // Next we will determine if the message should be sent. We give the developer
        // one final chance to stop this message and then we will send it to all of
        // its recipients. We will then fire the sent event for the sent message.
        $symfonyMessage = $message->getSymfonyMessage();

        if ($this->shouldSendMessage($symfonyMessage, $data)) {
            $symfonySentMessage = $this->sendSymfonyMessage($symfonyMessage);

            if ($symfonySentMessage) {
                $sentMessage = new SentMessage($symfonySentMessage);

                $this->dispatchSentEvent($sentMessage, $data);

                return $sentMessage;
            }
        }

        return null;
    }

    /**
     * Send the given mailable.
     *
     * @param MailableContract $mailable
     * @return SentMessage|null
     */
    protected function sendMailable(MailableContract $mailable): ?SentMessage
    {
        return $mailable->mailer($this->name)->send($this);
    }

    /**
     * Parse the given view name or array.
     *
     * @param array|string|\Closure $view
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView(array|string|Closure $view): array
    {
        if (is_string($view) || $view instanceof Closure) {
            return [$view, null, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since it should contain both views with numerical keys.
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If this view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via the
        // named keys instead, allowing the developers to use one or the other.
        if (is_array($view)) {
            return [
                $view['html'] ?? null,
                $view['text'] ?? null,
                $view['raw'] ?? null,
            ];
        }

        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Add the content to a given message.
     *
     * @param Message $message
     * @param string|null $view
     * @param string|null $plain
     * @param string|null $raw
     * @param array $data
     * @return void
     */
    protected function addContent(Message $message, $view = null, $plain = null, $raw = null, $data = []): void
    {
        if (isset($view)) {
            $message->html($this->renderView($view, $data) ?: ' ');
        }

        if (isset($plain)) {
            $message->text($this->renderView($plain, $data) ?: ' ');
        }

        if (isset($raw)) {
            $message->text($raw);
        }
    }

    /**
     * Render the given view.
     *
     * @param string|\Closure $view
     * @param array $data
     * @return string
     */
    protected function renderView(string|Closure $view, array $data): string
    {
        $view = value($view, $data);

        return $view instanceof Htmlable
            ? $view->toHtml()
            : $this->views->make($view, $data)->render();
    }

    /**
     * Set the global "to" address on the given message.
     *
     * @param Message $message
     * @return void
     */
    protected function setGlobalToAndRemoveCcAndBcc(Message $message): void
    {
        $message->forgetTo();

        $message->to($this->to['address'], $this->to['name'], true);

        $message->forgetCc();
        $message->forgetBcc();
    }

    /**
     * Create a new message instance.
     *
     * @return Message
     */
    protected function createMessage(): Message
    {
        $message = new Message(new Email());

        // If a global from address has been specified we will set it on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We'll just go ahead and push this address.
        if (!empty($this->from['address'])) {
            $message->from($this->from['address'], $this->from['name']);
        }

        // When a global reply address was specified we will set this on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push this address.
        if (!empty($this->replyTo['address'])) {
            $message->replyTo($this->replyTo['address'], $this->replyTo['name']);
        }

        if (!empty($this->returnPath['address'])) {
            $message->returnPath($this->returnPath['address']);
        }

        return $message;
    }

    /**
     * Send a Symfony Email instance.
     *
     * @param Email $message
     * @return \Symfony\Component\Mailer\SentMessage|null
     */
    protected function sendSymfonyMessage(Email $message): ?\Symfony\Component\Mailer\SentMessage
    {
        try {
            return $this->transport->send($message, Envelope::create($message));
        } finally {
            //
        }
    }

    /**
     * Determines if the email can be sent.
     *
     * @param Email $message
     * @param array $data
     * @return bool
     */
    protected function shouldSendMessage(Email $message, array $data = []): bool
    {
        if (!$this->events) {
            return true;
        }

        return $this->events->until(
                new MessageSending($message, $data)
            ) !== false;
    }

    /**
     * Dispatch the message sent event.
     *
     * @param SentMessage $message
     * @param array $data
     * @return void
     */
    protected function dispatchSentEvent(SentMessage $message, array $data = []): void
    {
        $this->events?->dispatch(
            new MessageSent($message, $data)
        );
    }

    /**
     * Get the Symfony Transport instance.
     *
     * @return TransportInterface
     */
    public function getSymfonyTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * Get the view factory instance.
     *
     * @return Factory
     */
    public function getViewFactory(): Factory
    {
        return $this->views;
    }

    /**
     * Set the Symfony Transport instance.
     *
     * @param TransportInterface $transport
     * @return void
     */
    public function setSymfonyTransport(TransportInterface $transport)
    {
        $this->transport = $transport;
    }
}
