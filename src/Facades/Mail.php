<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * @method static \Mini\Contracts\Mail\Mailer mailer(string|null $name = null)
 * @method static \Mini\Mail\Mailer driver(string|null $driver = null)
 * @method static \Symfony\Component\Mailer\Transport\TransportInterface createSymfonyTransport(array $config)
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static void purge(string|null $name = null)
 * @method static \Mini\Mail\MailManager extend(string $driver, \Closure $callback)
 * @method static \Mini\Contracts\Foundation\Application getApplication()
 * @method static \Mini\Mail\MailManager setApplication(\Mini\Contracts\App $app)
 * @method static \Mini\Mail\MailManager forgetMailers()
 * @method static void alwaysFrom(string $address, string|null $name = null)
 * @method static void alwaysReplyTo(string $address, string|null $name = null)
 * @method static void alwaysReturnPath(string $address)
 * @method static void alwaysTo(string $address, string|null $name = null)
 * @method static \Mini\Mail\PendingMail to(mixed $users, string|null $name = null)
 * @method static \Mini\Mail\PendingMail cc(mixed $users, string|null $name = null)
 * @method static \Mini\Mail\PendingMail bcc(mixed $users, string|null $name = null)
 * @method static \Mini\Mail\SentMessage|null html(string $html, mixed $callback)
 * @method static \Mini\Mail\SentMessage|null raw(string $text, mixed $callback)
 * @method static \Mini\Mail\SentMessage|null plain(string $view, array $data, mixed $callback)
 * @method static string render(string|array $view, array $data = [])
 * @method static \Mini\Mail\SentMessage|null send(\Mini\Contracts\Mail\Mailable|string|array $view, array $data = [], \Closure|string|null $callback = null)
 * @method static void queue(\Mini\Contracts\Mail\Mailable|string|array $view, \Closure|string|null $callable = null)
 * @method static void later(\Mini\Contracts\Mail\Mailable|string|array $view, int $delay = 10, \Closure|string|null $callable = null)
 * @method static void laterOn(\Mini\Contracts\Mail\Mailable|string|array $view, \DateTimeInterface $dateTime, \Closure|string|null $callable = null)
 * @method static \Symfony\Component\Mailer\Transport\TransportInterface getSymfonyTransport()
 * @method static \Mini\Contracts\View\Factory getViewFactory()
 * @method static void setSymfonyTransport(\Symfony\Component\Mailer\Transport\TransportInterface $transport)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static \Mini\Support\Collection sent(string|\Closure $mailable, callable|null $callback = null)
 * @method static bool hasSent(string $mailable)
 *
 * @see \Mini\Mail\MailManager
 */
class Mail extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mail.manager';
    }
}
