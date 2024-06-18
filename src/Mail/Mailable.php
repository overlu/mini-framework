<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail;

use Closure;
use Mini\Config\Repository as ConfigRepository;
use Mini\Container\Container;
use Mini\Container\EntryNotFoundException;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Filesystem\Factory as FilesystemFactory;
use Mini\Contracts\Mail\Attachable;
use Mini\Contracts\Mail\Factory as MailFactory;
use Mini\Contracts\Mail\Mailable as MailableContract;
use Mini\Contracts\Support\Renderable;
use Mini\Contracts\Translation\HasLocalePreference;
use Mini\Support\Collection;
use Mini\Support\HtmlString;
use Mini\Support\Str;
use Mini\Support\Traits\Conditionable;
use Mini\Support\Traits\ForwardsCalls;
use Mini\Support\Traits\Localizable;
use Mini\Support\Traits\Macroable;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mime\Address;

class Mailable implements MailableContract, Renderable
{
    use Conditionable, ForwardsCalls, Localizable, Macroable {
        __call as macroCall;
    }

    /**
     * The locale of the message.
     *
     * @var string
     */
    public string $locale;

    /**
     * The person the message is from.
     *
     * @var array
     */
    public array $from = [];

    /**
     * The "to" recipients of the message.
     *
     * @var array
     */
    public array $to = [];

    /**
     * The "cc" recipients of the message.
     *
     * @var array
     */
    public array $cc = [];

    /**
     * The "bcc" recipients of the message.
     *
     * @var array
     */
    public array $bcc = [];

    /**
     * The "reply to" recipients of the message.
     *
     * @var array
     */
    public array $replyTo = [];

    /**
     * The subject of the message.
     *
     * @var string
     */
    public string $subject;

    /**
     * The Markdown template for the message (if applicable).
     *
     * @var string
     */
    public string $markdown;

    /**
     * The HTML to use for the message.
     *
     * @var string
     */
    protected string $html;

    /**
     * The view to use for the message.
     *
     * @var string
     */
    public string $view;

    /**
     * The plain text view to use for the message.
     *
     * @var string
     */
    public string $textView;

    /**
     * The view data for the message.
     *
     * @var array
     */
    public array $viewData = [];

    /**
     * The attachments for the message.
     *
     * @var array
     */
    public array $attachments = [];

    /**
     * The raw attachments for the message.
     *
     * @var array
     */
    public array $rawAttachments = [];

    /**
     * The attachments from a storage disk.
     *
     * @var array
     */
    public array $diskAttachments = [];

    /**
     * The tags for the message.
     *
     * @var array
     */
    protected array $tags = [];

    /**
     * The metadata for the message.
     *
     * @var array
     */
    protected array $metadata = [];

    /**
     * The callbacks for the message.
     *
     * @var array
     */
    public array $callbacks = [];

    /**
     * The name of the theme that should be used when formatting the message.
     *
     * @var string|null
     */
    public ?string $theme = null;

    /**
     * The name of the mailer that should send the message.
     *
     * @var string
     */
    public string $mailer;

    /**
     * The rendered mailable views for testing / assertions.
     *
     * @var array
     */
    protected array $assertionableRenderStrings;

    /**
     * The callback that should be invoked while building the view data.
     *
     * @var callable
     */
    public static $viewDataCallback;

    /**
     * Send the message using the given mailer.
     *
     * @param \Mini\Contracts\Mail\Mailer|MailFactory $mailer
     * @return SentMessage|null
     * @throws ReflectionException|BindingResolutionException
     */
    public function send(MailFactory|\Mini\Contracts\Mail\Mailer $mailer): SentMessage|null
    {
        return $this->withLocale($this->locale, function () use ($mailer) {
            $this->prepareMailableForDelivery();

            $mailer = $mailer instanceof MailFactory
                ? $mailer->mailer($this->mailer)
                : $mailer;

            return $mailer->send($this->buildView(), $this->buildViewData(), function ($message) {
                $this->buildFrom($message)
                    ->buildRecipients($message)
                    ->buildSubject($message)
                    ->buildTags($message)
                    ->buildMetadata($message)
                    ->runCallbacks($message)
                    ->buildAttachments($message);
            });
        });
    }

    /**
     * Render the mailable into a view.
     *
     * @return string
     *
     * @throws ReflectionException|BindingResolutionException
     */
    public function render(): string
    {
        return $this->withLocale($this->locale, function () {
            $this->prepareMailableForDelivery();

            return Container::getInstance()->make('mailer')->render(
                $this->buildView(), $this->buildViewData()
            );
        });
    }

    /**
     * Build the view for the message.
     *
     * @return array|string
     *
     * @throws ReflectionException
     */
    protected function buildView(): array|string
    {
        if (isset($this->html)) {
            return array_filter([
                'html' => new HtmlString($this->html),
                'text' => $this->textView ?? null,
            ]);
        }

        if (isset($this->markdown)) {
            return $this->buildMarkdownView();
        }

        if (isset($this->view, $this->textView)) {
            return [$this->view, $this->textView];
        }

        if (isset($this->textView)) {
            return ['text' => $this->textView];
        }

        return $this->view;
    }

    /**
     * Build the Markdown view for the message.
     *
     * @return array
     *
     * @throws ReflectionException
     */
    protected function buildMarkdownView(): array
    {
        $data = $this->buildViewData();

        return [
            'html' => $this->buildMarkdownHtml($data),
            'text' => $this->buildMarkdownText($data),
        ];
    }

    /**
     * Build the view data for the message.
     *
     * @return array
     *
     * @throws ReflectionException
     */
    public function buildViewData(): array
    {
        $data = $this->viewData;

        if (static::$viewDataCallback) {
            $data = array_merge($data, call_user_func(static::$viewDataCallback, $this));
        }

        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }

        return $data;
    }

    /**
     * Build the HTML view for a Markdown message.
     *
     * @param array $viewData
     * @return Closure
     */
    protected function buildMarkdownHtml(array $viewData): callable
    {
        return fn($data) => $this->markdownRenderer()->render(
            $this->markdown,
            array_merge($data, $viewData),
        );
    }

    /**
     * Build the text view for a Markdown message.
     *
     * @param array $viewData
     * @return Closure
     */
    protected function buildMarkdownText(array $viewData): callable
    {
        return function ($data) use ($viewData) {
            if (isset($data['message'])) {
                $data = array_merge($data, [
                    'message' => new TextMessage($data['message']),
                ]);
            }

            return $this->textView ?? $this->markdownRenderer()->renderText(
                    $this->markdown,
                    array_merge($data, $viewData)
                );
        };
    }

    /**
     * Resolves a Markdown instance with the mail's theme.
     *
     * @return Markdown
     * @throws EntryNotFoundException
     */
    protected function markdownRenderer(): Markdown
    {
        return tap(Container::getInstance()->make(Markdown::class), function ($markdown) {
            $markdown->theme($this->theme ?: Container::getInstance()->get(ConfigRepository::class)->get(
                'mail.markdown.theme', 'default')
            );
        });
    }

    /**
     * Add the sender to the message.
     *
     * @param Message $message
     * @return $this
     */
    protected function buildFrom(Message $message): self
    {
        if (!empty($this->from)) {
            $message->from($this->from[0]['address'], $this->from[0]['name']);
        }

        return $this;
    }

    /**
     * Add all of the recipients to the message.
     *
     * @param Message $message
     * @return $this
     */
    protected function buildRecipients(Message $message): self
    {
        foreach (['to', 'cc', 'bcc', 'replyTo'] as $type) {
            foreach ($this->{$type} as $recipient) {
                $message->{$type}($recipient['address'], $recipient['name']);
            }
        }

        return $this;
    }

    /**
     * Set the subject for the message.
     *
     * @param Message $message
     * @return $this
     */
    protected function buildSubject(Message $message): self
    {
        if ($this->subject) {
            $message->subject($this->subject);
        } else {
            $message->subject(Str::title(Str::snake(class_basename($this), ' ')));
        }

        return $this;
    }

    /**
     * Add all of the attachments to the message.
     *
     * @param Message $message
     * @return $this
     */
    protected function buildAttachments(Message $message): self
    {
        foreach ($this->attachments as $attachment) {
            $message->attach($attachment['file'], $attachment['options']);
        }

        foreach ($this->rawAttachments as $attachment) {
            $message->attachData(
                $attachment['data'], $attachment['name'], $attachment['options']
            );
        }

        $this->buildDiskAttachments($message);

        return $this;
    }

    /**
     * Add all of the disk attachments to the message.
     *
     * @param Message $message
     * @return void
     */
    protected function buildDiskAttachments(Message $message): void
    {
        foreach ($this->diskAttachments as $attachment) {
            $storage = Container::getInstance()->make(
                FilesystemFactory::class
            )->disk($attachment['disk']);

            $message->attachData(
                $storage->get($attachment['path']),
                $attachment['name'] ?? basename($attachment['path']),
                array_merge(['mime' => $storage->mimeType($attachment['path'])], $attachment['options'])
            );
        }
    }

    /**
     * Add all defined tags to the message.
     *
     * @param Message $message
     * @return $this
     */
    protected function buildTags(Message $message): self
    {
        if ($this->tags) {
            foreach ($this->tags as $tag) {
                $message->getHeaders()->add(new TagHeader($tag));
            }
        }

        return $this;
    }

    /**
     * Add all defined metadata to the message.
     *
     * @param Message $message
     * @return $this
     */
    protected function buildMetadata(Message $message): self
    {
        if ($this->metadata) {
            foreach ($this->metadata as $key => $value) {
                $message->getHeaders()->add(new MetadataHeader($key, $value));
            }
        }

        return $this;
    }

    /**
     * Run the callbacks for the message.
     *
     * @param Message $message
     * @return $this
     */
    protected function runCallbacks(Message $message): self
    {
        foreach ($this->callbacks as $callback) {
            $callback($message->getSymfonyMessage());
        }

        return $this;
    }

    /**
     * Set the locale of the message.
     *
     * @param string $locale
     * @return $this
     */
    public function locale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the priority of this message.
     *
     * The value is an integer where 1 is the highest priority and 5 is the lowest.
     *
     * @param int $level
     * @return $this
     */
    public function priority(int $level = 3): self
    {
        $this->callbacks[] = function ($message) use ($level) {
            $message->priority($level);
        };

        return $this;
    }

    /**
     * Set the sender of the message.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return $this
     */
    public function from(object|array|string $address, string $name = null): self
    {
        return $this->setAddress($address, $name, 'from');
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return bool
     */
    public function hasFrom(object|array|string $address, string $name = null): bool
    {
        return $this->hasRecipient($address, $name, 'from');
    }

    /**
     * Set the recipients of the message.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return $this
     */
    public function to(object|array|string $address, string $name = null): self
    {
        if (!$this->locale && $address instanceof HasLocalePreference) {
            $this->locale($address->preferredLocale());
        }

        return $this->setAddress($address, $name, 'to');
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return bool
     */
    public function hasTo(object|array|string $address, string $name = null): bool
    {
        return $this->hasRecipient($address, $name, 'to');
    }

    /**
     * Set the recipients of the message.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return $this
     */
    public function cc(object|array|string $address, string $name = null): self
    {
        return $this->setAddress($address, $name, 'cc');
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return bool
     */
    public function hasCc(object|array|string $address, string $name = null): bool
    {
        return $this->hasRecipient($address, $name, 'cc');
    }

    /**
     * Set the recipients of the message.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return $this
     */
    public function bcc(object|array|string $address, string $name = null): self
    {
        return $this->setAddress($address, $name, 'bcc');
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return bool
     */
    public function hasBcc(object|array|string $address, string $name = null): bool
    {
        return $this->hasRecipient($address, $name, 'bcc');
    }

    /**
     * Set the "reply to" address of the message.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return $this
     */
    public function replyTo(object|array|string $address, string $name = null): self
    {
        return $this->setAddress($address, $name, 'replyTo');
    }

    /**
     * Determine if the given replyTo is set on the mailable.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return bool
     */
    public function hasReplyTo(object|array|string $address, string $name = null): bool
    {
        return $this->hasRecipient($address, $name, 'replyTo');
    }

    /**
     * Set the recipients of the message.
     *
     * All recipients are stored internally as [['name' => ?, 'address' => ?]]
     *
     * @param object|array|string $address
     * @param string|null $name
     * @param string $property
     * @return $this
     */
    protected function setAddress(object|array|string $address, string $name = null, string $property = 'to'): self
    {
        if (empty($address)) {
            return $this;
        }

        foreach ($this->addressesToArray($address, $name) as $recipient) {
            $recipient = $this->normalizeRecipient($recipient);

            $this->{$property}[] = [
                'name' => $recipient->name ?? null,
                'address' => $recipient->email,
            ];
        }

        $this->{$property} = collect($this->{$property})
            ->reverse()
            ->unique('address')
            ->reverse()
            ->values()
            ->all();

        return $this;
    }

    /**
     * Convert the given recipient arguments to an array.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return array
     */
    protected function addressesToArray(object|array|string $address, string $name = null): array
    {
        if (!is_array($address) && !$address instanceof Collection) {
            $address = is_string($name) ? [['name' => $name, 'email' => $address]] : [$address];
        }

        return $address;
    }

    /**
     * Convert the given recipient into an object.
     *
     * @param mixed $recipient
     * @return object
     */
    protected function normalizeRecipient(mixed $recipient): object
    {
        if (is_array($recipient)) {
            if (array_is_list($recipient)) {
                return (object)array_map(function ($email) {
                    return compact('email');
                }, $recipient);
            }

            return (object)$recipient;
        }

        if (is_string($recipient)) {
            return (object)['email' => $recipient];
        }

        if ($recipient instanceof Address) {
            return (object)['email' => $recipient->getAddress(), 'name' => $recipient->getName()];
        }

        if ($recipient instanceof Mailables\Address) {
            return (object)['email' => $recipient->address, 'name' => $recipient->name];
        }

        return $recipient;
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @param string $property
     * @return bool
     */
    protected function hasRecipient(object|array|string $address, string $name = null, string $property = 'to'): bool
    {
        if (empty($address)) {
            return false;
        }

        $expected = $this->normalizeRecipient(
            $this->addressesToArray($address, $name)[0]
        );

        $expected = [
            'name' => $expected->name ?? null,
            'address' => $expected->email,
        ];

        if ($this->hasEnvelopeRecipient($expected['address'], $expected['name'], $property)) {
            return true;
        }

        return collect($this->{$property})->contains(function ($actual) use ($expected) {
            if (!isset($expected['name'])) {
                return $actual['address'] === $expected['address'];
            }

            return $actual === $expected;
        });
    }

    /**
     * Determine if the mailable "envelope" method defines a recipient.
     *
     * @param string $address
     * @param string $name
     * @param string $property
     * @return bool
     */
    private function hasEnvelopeRecipient(string $address, string $name, string $property): bool
    {
        return method_exists($this, 'envelope') && match ($property) {
                'from' => $this->envelope()->isFrom($address, $name),
                'to' => $this->envelope()->hasTo($address, $name),
                'cc' => $this->envelope()->hasCc($address, $name),
                'bcc' => $this->envelope()->hasBcc($address, $name),
                'replyTo' => $this->envelope()->hasReplyTo($address, $name),
            };
    }

    /**
     * Set the subject of the message.
     *
     * @param string $subject
     * @return $this
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Determine if the mailable has the given subject.
     *
     * @param string $subject
     * @return bool
     */
    public function hasSubject(string $subject): bool
    {
        return $this->subject === $subject ||
            (method_exists($this, 'envelope') && $this->envelope()->hasSubject($subject));
    }

    /**
     * Set the Markdown template for the message.
     *
     * @param string $view
     * @param array $data
     * @return $this
     */
    public function markdown(string $view, array $data = []): self
    {
        $this->markdown = $view;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the view and view data for the message.
     *
     * @param string $view
     * @param array $data
     * @return $this
     */
    public function view(string $view, array $data = []): self
    {
        $this->view = $view;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the rendered HTML content for the message.
     *
     * @param string $html
     * @return $this
     */
    public function html(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Set the plain text view for the message.
     *
     * @param string $textView
     * @param array $data
     * @return $this
     */
    public function text(string $textView, array $data = []): self
    {
        $this->textView = $textView;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the view data for the message.
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return $this
     */
    public function with(array|string $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

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
            return $file->attachTo($this, $options);
        }

        $this->attachments = collect($this->attachments)
            ->push(compact('file', 'options'))
            ->unique('file')
            ->all();

        return $this;
    }

    /**
     * Attach multiple files to the message.
     *
     * @param array $files
     * @return $this
     */
    public function attachMany(array $files): self
    {
        foreach ($files as $file => $options) {
            if (is_int($file)) {
                $this->attach($options);
            } else {
                $this->attach($file, $options);
            }
        }

        return $this;
    }

    /**
     * Determine if the mailable has the given attachment.
     *
     * @param string|Attachable|Attachment $file
     * @param array $options
     * @return bool
     */
    public function hasAttachment(string|Attachment|Attachable $file, array $options = []): bool
    {
        if ($file instanceof Attachable) {
            $file = $file->toMailAttachment();
        }

        if ($file instanceof Attachment && $this->hasEnvelopeAttachment($file, $options)) {
            return true;
        }

        if ($file instanceof Attachment) {
            $parts = $file->attachWith(
                fn($path) => [$path, [
                    'as' => $options['as'] ?? $file->as,
                    'mime' => $options['mime'] ?? $file->mime,
                ]],
                fn($data) => $this->hasAttachedData($data(), $options['as'] ?? $file->as, ['mime' => $options['mime'] ?? $file->mime])
            );

            if ($parts === true) {
                return true;
            }

            [$file, $options] = $parts === false
                ? [null, []]
                : $parts;
        }

        return collect($this->attachments)->contains(
            fn($attachment) => $attachment['file'] === $file && array_filter($attachment['options']) === array_filter($options)
        );
    }

    /**
     * Determine if the mailable has the given envelope attachment.
     *
     * @param Attachment $attachment
     * @param array $options
     * @return bool
     */
    private function hasEnvelopeAttachment(Attachment $attachment, array $options = []): bool
    {
        if (!method_exists($this, 'envelope')) {
            return false;
        }

        $attachments = $this->attachments();

        return Collection::make(is_object($attachments) ? [$attachments] : $attachments)
            ->map(fn($attached) => $attached instanceof Attachable ? $attached->toMailAttachment() : $attached)
            ->contains(fn($attached) => $attached->isEquivalent($attachment, $options));
    }

    /**
     * Attach a file to the message from storage.
     *
     * @param string $path
     * @param string|null $name
     * @param array $options
     * @return $this
     */
    public function attachFromStorage(string $path, string $name = null, array $options = []): self
    {
        return $this->attachFromStorageDisk(null, $path, $name, $options);
    }

    /**
     * Attach a file to the message from storage.
     *
     * @param string $disk
     * @param string $path
     * @param string|null $name
     * @param array $options
     * @return $this
     */
    public function attachFromStorageDisk(string $disk, string $path, string $name = null, array $options = []): self
    {
        $this->diskAttachments = collect($this->diskAttachments)->push([
            'disk' => $disk,
            'path' => $path,
            'name' => $name ?? basename($path),
            'options' => $options,
        ])->unique(function ($file) {
            return $file['name'] . $file['disk'] . $file['path'];
        })->all();

        return $this;
    }

    /**
     * Determine if the mailable has the given attachment from storage.
     *
     * @param string $path
     * @param string|null $name
     * @param array $options
     * @return bool
     */
    public function hasAttachmentFromStorage(string $path, string $name = null, array $options = []): bool
    {
        return $this->hasAttachmentFromStorageDisk(null, $path, $name, $options);
    }

    /**
     * Determine if the mailable has the given attachment from a specific storage disk.
     *
     * @param string $disk
     * @param string $path
     * @param string|null $name
     * @param array $options
     * @return bool
     */
    public function hasAttachmentFromStorageDisk(string $disk, string $path, string $name = null, array $options = []): bool
    {
        return collect($this->diskAttachments)->contains(
            fn($attachment) => $attachment['disk'] === $disk
                && $attachment['path'] === $path
                && $attachment['name'] === ($name ?? basename($path))
                && $attachment['options'] === $options
        );
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
        $this->rawAttachments = collect($this->rawAttachments)
            ->push(compact('data', 'name', 'options'))
            ->unique(function ($file) {
                return $file['name'] . $file['data'];
            })->all();

        return $this;
    }

    /**
     * Determine if the mailable has the given data as an attachment.
     *
     * @param string $data
     * @param string $name
     * @param array $options
     * @return bool
     */
    public function hasAttachedData(string $data, string $name, array $options = []): bool
    {
        return collect($this->rawAttachments)->contains(
            fn($attachment) => $attachment['data'] === $data
                && $attachment['name'] === $name
                && array_filter($attachment['options']) === array_filter($options)
        );
    }

    /**
     * Add a tag header to the message when supported by the underlying transport.
     *
     * @param string $value
     * @return $this
     */
    public function tag(string $value): self
    {
        $this->tags[] = $value;

        return $this;
    }

    /**
     * Determine if the mailable has the given tag.
     *
     * @param string $value
     * @return bool
     */
    public function hasTag(string $value): bool
    {
        return in_array($value, $this->tags, true) ||
            (method_exists($this, 'envelope') && in_array($value, $this->envelope()->tags, true));
    }

    /**
     * Add a metadata header to the message when supported by the underlying transport.
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function metadata(string $key, string $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Determine if the mailable has the given metadata.
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function hasMetadata(string $key, string $value): bool
    {
        return (isset($this->metadata[$key]) && $this->metadata[$key] === $value) ||
            (method_exists($this, 'envelope') && $this->envelope()->hasMetadata($key, $value));
    }

    /**
     * Format the mailable recipient for display in an assertion message.
     *
     * @param object|array|string $address
     * @param string|null $name
     * @return string
     */
    private function formatAssertionRecipient(object|array|string $address, string $name = null): string
    {
        if (!is_string($address)) {
            $address = json_encode($address);
        }

        if (filled($name)) {
            $address .= ' (' . $name . ')';
        }

        return $address;
    }


    /**
     * Prepare the mailable instance for delivery.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function prepareMailableForDelivery(): void
    {
        if (method_exists($this, 'build')) {
            Container::getInstance()->call([$this, 'build']);
        }

        $this->ensureHeadersAreHydrated();
        $this->ensureEnvelopeIsHydrated();
        $this->ensureContentIsHydrated();
        $this->ensureAttachmentsAreHydrated();
    }

    /**
     * Ensure the mailable's headers are hydrated from the "headers" method.
     *
     * @return void
     */
    private function ensureHeadersAreHydrated(): void
    {
        if (!method_exists($this, 'headers')) {
            return;
        }

        $headers = $this->headers();

        $this->withSymfonyMessage(function ($message) use ($headers) {
            if ($headers->messageId) {
                $message->getHeaders()->addIdHeader('Message-Id', $headers->messageId);
            }

            if (count($headers->references) > 0) {
                $message->getHeaders()->addTextHeader('References', $headers->referencesString());
            }

            foreach ($headers->text as $key => $value) {
                $message->getHeaders()->addTextHeader($key, $value);
            }
        });
    }

    /**
     * Ensure the mailable's "envelope" data is hydrated from the "envelope" method.
     *
     * @return void
     */
    private function ensureEnvelopeIsHydrated(): void
    {
        if (!method_exists($this, 'envelope')) {
            return;
        }

        $envelope = $this->envelope();

        if (isset($envelope->from)) {
            $this->from($envelope->from->address, $envelope->from->name);
        }

        foreach (['to', 'cc', 'bcc', 'replyTo'] as $type) {
            foreach ($envelope->{$type} as $address) {
                $this->{$type}($address->address, $address->name);
            }
        }

        if ($envelope->subject) {
            $this->subject($envelope->subject);
        }

        foreach ($envelope->tags as $tag) {
            $this->tag($tag);
        }

        foreach ($envelope->metadata as $key => $value) {
            $this->metadata($key, $value);
        }

        foreach ($envelope->using as $callback) {
            $this->withSymfonyMessage($callback);
        }
    }

    /**
     * Ensure the mailable's content is hydrated from the "content" method.
     *
     * @return void
     */
    private function ensureContentIsHydrated(): void
    {
        if (!method_exists($this, 'content')) {
            return;
        }

        $content = $this->content();

        if ($content->view) {
            $this->view($content->view);
        }

        if ($content->html) {
            $this->view($content->html);
        }

        if ($content->text) {
            $this->text($content->text);
        }

        if ($content->markdown) {
            $this->markdown($content->markdown);
        }

        if ($content->htmlString) {
            $this->html($content->htmlString);
        }

        foreach ($content->with as $key => $value) {
            $this->with($key, $value);
        }
    }

    /**
     * Ensure the mailable's attachments are hydrated from the "attachments" method.
     *
     * @return void
     */
    private function ensureAttachmentsAreHydrated(): void
    {
        if (!method_exists($this, 'attachments')) {
            return;
        }

        $attachments = $this->attachments();

        Collection::make(is_object($attachments) ? [$attachments] : $attachments)
            ->each(function ($attachment) {
                $this->attach($attachment);
            });
    }

    /**
     * Set the name of the mailer that should send the message.
     *
     * @param string $mailer
     * @return $this
     */
    public function mailer(string $mailer): self
    {
        $this->mailer = $mailer;

        return $this;
    }

    /**
     * Register a callback to be called with the Symfony message instance.
     *
     * @param callable $callback
     * @return $this
     */
    public function withSymfonyMessage(callable $callback): self
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called while building the view data.
     *
     * @param callable $callback
     * @return void
     */
    public static function buildViewDataUsing(callable $callback): void
    {
        static::$viewDataCallback = $callback;
    }

    /**
     * Dynamically bind parameters to the message.
     *
     * @param string $method
     * @param array $parameters
     * @return $this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (str_starts_with($method, 'with')) {
            return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
        }

        static::throwBadMethodCallException($method);
    }
}
