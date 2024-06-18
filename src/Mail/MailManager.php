<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail;

use Aws\Ses\SesClient;
use Aws\SesV2\SesV2Client;
use Closure;
use Mini\Contracts\App;
use Mini\Contracts\Mail\Factory as FactoryContract;
use Mini\Mail\Transport\ArrayTransport;
use Mini\Mail\Transport\LogTransport;
use Mini\Mail\Transport\SesTransport;
use Mini\Mail\Transport\SesV2Transport;
use Mini\Support\Arr;
use Mini\Support\ConfigurationUrlParser;
use Mini\Support\Str;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkApiTransport;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mailer\Transport\RoundRobinTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @mixin Mailer
 */
class MailManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var App
     */
    protected App $app;

    /**
     * The array of resolved mailers.
     *
     * @var array
     */
    protected array $mailers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected array $customCreators = [];

    /**
     * Create a new Mail manager instance.
     *
     * @param App $app
     * @return void
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Get a mailer instance by name.
     *
     * @param string|null $name
     * @return Mailer
     */
    public function mailer($name = null): Mailer
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->mailers[$name] = $this->get($name);
    }

    /**
     * Get a mailer driver instance.
     *
     * @param string|null $driver
     * @return Mailer
     */
    public function driver(string $driver = null): Mailer
    {
        return $this->mailer($driver);
    }

    /**
     * Attempt to get the mailer from the local cache.
     *
     * @param string $name
     * @return Mailer
     */
    protected function get(string $name): Mailer
    {
        return $this->mailers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given mailer.
     *
     * @param string $name
     * @return Mailer
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): Mailer
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Mailer [{$name}] is not defined.");
        }

        // Once we have created the mailer instance we will set a container instance
        // on the mailer. This allows us to resolve mailer classes via containers
        // for maximum testability on said classes instead of passing Closures.
        $mailer = new Mailer(
            $name,
            $this->app['view'],
            $this->createSymfonyTransport($config),
            $this->app['events']
        );

        // Next we will set all of the global addresses on this mailer, which allows
        // for easy unification of all "from" addresses as well as easy debugging
        // of sent messages since these will be sent to a single email address.
        foreach (['from', 'reply_to', 'to', 'return_path'] as $type) {
            $this->setGlobalAddress($mailer, $config, $type);
        }

        return $mailer;
    }

    /**
     * Create a new transport instance.
     *
     * @param array $config
     * @return TransportInterface
     *
     * @throws \InvalidArgumentException
     */
    public function createSymfonyTransport(array $config): TransportInterface
    {
        // Here we will check if the "transport" key exists and if it doesn't we will
        // assume an application is still using the legacy mail configuration file
        // format and use the "mail.driver" configuration option instead for BC.
        $transport = $config['transport'] ?? $this->app['config']['mail.driver'];

        if (isset($this->customCreators[$transport])) {
            return call_user_func($this->customCreators[$transport], $config);
        }

        if (trim($transport ?? '') === '' ||
            !method_exists($this, $method = 'create' . ucfirst(Str::camel($transport)) . 'Transport')) {
            throw new InvalidArgumentException("Unsupported mail transport [{$transport}].");
        }

        return $this->{$method}($config);
    }

    /**
     * Create an instance of the Symfony SMTP Transport driver.
     *
     * @param array $config
     * @return EsmtpTransport
     */
    protected function createSmtpTransport(array $config): EsmtpTransport
    {
        $factory = new EsmtpTransportFactory;

        $scheme = $config['scheme'] ?? null;

        if (!$scheme) {
            $scheme = !empty($config['encryption']) && $config['encryption'] === 'tls'
                ? (((int)$config['port'] === 465) ? 'smtps' : 'smtp')
                : '';
        }

        $transport = $factory->create(new Dsn(
            $scheme,
            $config['host'],
            $config['username'] ?? null,
            $config['password'] ?? null,
            $config['port'] ?? null,
            $config
        ));

        return $this->configureSmtpTransport($transport, $config);
    }

    /**
     * Configure the additional SMTP driver options.
     *
     * @param EsmtpTransport $transport
     * @param array $config
     * @return EsmtpTransport
     */
    protected function configureSmtpTransport(EsmtpTransport $transport, array $config): EsmtpTransport
    {
        $stream = $transport->getStream();

        if ($stream instanceof SocketStream) {
            if (isset($config['source_ip'])) {
                $stream->setSourceIp($config['source_ip']);
            }

            if (isset($config['timeout'])) {
                $stream->setTimeout($config['timeout']);
            }
        }

        return $transport;
    }

    /**
     * Create an instance of the Symfony Sendmail Transport driver.
     *
     * @param array $config
     * @return SendmailTransport
     */
    protected function createSendmailTransport(array $config): SendmailTransport
    {
        return new SendmailTransport(
            $config['path'] ?? $this->app['config']->get('mail.sendmail')
        );
    }

    /**
     * Create an instance of the Symfony Amazon SES Transport driver.
     *
     * @param array $config
     * @return SesTransport
     */
    protected function createSesTransport(array $config): SesTransport
    {
        $config = array_merge(
            $this->app['config']->get('services.ses', []),
            ['version' => 'latest', 'service' => 'email'],
            $config
        );

        $config = Arr::except($config, ['transport']);

        return new SesTransport(
            new SesClient($this->addSesCredentials($config)),
            $config['options'] ?? []
        );
    }

    /**
     * Create an instance of the Symfony Amazon SES V2 Transport driver.
     *
     * @param array $config
     * @return SesV2Transport
     */
    protected function createSesV2Transport(array $config): SesV2Transport
    {
        $config = array_merge(
            $this->app['config']->get('services.ses', []),
            ['version' => 'latest'],
            $config
        );

        $config = Arr::except($config, ['transport']);

        return new SesV2Transport(
            new SesV2Client($this->addSesCredentials($config)),
            $config['options'] ?? []
        );
    }

    /**
     * Add the SES credentials to the configuration array.
     *
     * @param array $config
     * @return array
     */
    protected function addSesCredentials(array $config): array
    {
        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return Arr::except($config, ['token']);
    }

    /**
     * Create an instance of the Symfony Mail Transport driver.
     *
     * @return SendmailTransport
     */
    protected function createMailTransport(): SendmailTransport
    {
        return new SendmailTransport;
    }

    /**
     * Create an instance of the Symfony Mailgun Transport driver.
     *
     * @param array $config
     * @return TransportInterface
     */
    protected function createMailgunTransport(array $config): TransportInterface
    {
        $factory = new MailgunTransportFactory(null, $this->getHttpClient($config));

        if (!isset($config['secret'])) {
            $config = $this->app['config']->get('services.mailgun', []);
        }

        return $factory->create(new Dsn(
            'mailgun+' . ($config['scheme'] ?? 'https'),
            $config['endpoint'] ?? 'default',
            $config['secret'],
            $config['domain']
        ));
    }

    /**
     * Create an instance of the Symfony Postmark Transport driver.
     *
     * @param array $config
     * @return PostmarkApiTransport
     */
    protected function createPostmarkTransport(array $config): PostmarkApiTransport
    {
        $factory = new PostmarkTransportFactory(null, $this->getHttpClient($config));

        $options = isset($config['message_stream_id'])
            ? ['message_stream' => $config['message_stream_id']]
            : [];

        return $factory->create(new Dsn(
            'postmark+api',
            'default',
            $config['token'] ?? $this->app['config']->get('services.postmark.token'),
            null,
            null,
            $options
        ));
    }

    /**
     * Create an instance of the Symfony Failover Transport driver.
     *
     * @param array $config
     * @return FailoverTransport
     */
    protected function createFailoverTransport(array $config): FailoverTransport
    {
        $transports = [];

        foreach ($config['mailers'] as $name) {
            $config = $this->getConfig($name);

            if (is_null($config)) {
                throw new InvalidArgumentException("Mailer [{$name}] is not defined.");
            }

            // Now, we will check if the "driver" key exists and if it does we will set
            // the transport configuration parameter in order to offer compatibility
            // with any Laravel <= 6.x application style mail configuration files.
            $transports[] = $this->app['config']['mail.driver']
                ? $this->createSymfonyTransport(array_merge($config, ['transport' => $name]))
                : $this->createSymfonyTransport($config);
        }

        return new FailoverTransport($transports);
    }

    /**
     * Create an instance of the Symfony Roundrobin Transport driver.
     *
     * @param array $config
     * @return RoundRobinTransport
     */
    protected function createRoundrobinTransport(array $config): RoundRobinTransport
    {
        $transports = [];

        foreach ($config['mailers'] as $name) {
            $config = $this->getConfig($name);

            if (is_null($config)) {
                throw new InvalidArgumentException("Mailer [{$name}] is not defined.");
            }

            // Now, we will check if the "driver" key exists and if it does we will set
            // the transport configuration parameter in order to offer compatibility
            // with any Laravel <= 6.x application style mail configuration files.
            $transports[] = $this->app['config']['mail.driver']
                ? $this->createSymfonyTransport(array_merge($config, ['transport' => $name]))
                : $this->createSymfonyTransport($config);
        }

        return new RoundRobinTransport($transports);
    }

    /**
     * Create an instance of the Array Transport Driver.
     *
     * @return ArrayTransport
     */
    protected function createArrayTransport()
    {
        return new ArrayTransport;
    }

    /**
     * Get a configured Symfony HTTP client instance.
     *
     * @return HttpClientInterface|null
     */
    protected function getHttpClient(array $config)
    {
        if ($options = ($config['client'] ?? false)) {
            $maxHostConnections = Arr::pull($options, 'max_host_connections', 6);
            $maxPendingPushes = Arr::pull($options, 'max_pending_pushes', 50);

            return HttpClient::create($options, $maxHostConnections, $maxPendingPushes);
        }
    }

    /**
     * Set a global address on the mailer by type.
     *
     * @param Mailer $mailer
     * @param array $config
     * @param string $type
     * @return void
     */
    protected function setGlobalAddress(Mailer $mailer, array $config, string $type): void
    {
        $address = Arr::get($config, $type, $this->app['config']['mail.' . $type]);

        if (is_array($address) && isset($address['address'])) {
            $mailer->{'always' . Str::studly($type)}($address['address'], $address['name']);
        }
    }

    /**
     * Get the mail connection configuration.
     *
     * @param string $name
     * @return array
     */
    protected function getConfig(string $name): array
    {
        // Here we will check if the "driver" key exists and if it does we will use
        // the entire mail configuration file as the "driver" config in order to
        // provide "BC" for any Laravel <= 6.x style mail configuration files.
        $config = $this->app['config']['mail.driver']
            ? $this->app['config']['mail']
            : $this->app['config']["mail.mailers.{$name}"];

        if (isset($config['url'])) {
            $config = array_merge($config, (new ConfigurationUrlParser)->parseConfiguration($config));

            $config['transport'] = Arr::pull($config, 'driver');
        }

        return $config;
    }

    /**
     * Get the default mail driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        // Here we will check if the "driver" key exists and if it does we will use
        // that as the default driver in order to provide support for old styles
        // of the Laravel mail configuration file for backwards compatibility.
        return $this->app['config']['mail.driver'] ??
            $this->app['config']['mail.default'];
    }

    /**
     * Set the default mail driver name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultDriver(string $name): void
    {
        if ($this->app['config']['mail.driver']) {
            $this->app['config']['mail.driver'] = $name;
        }

        $this->app['config']['mail.default'] = $name;
    }

    /**
     * Disconnect the given mailer and remove from local cache.
     *
     * @param string|null $name
     * @return void
     */
    public function purge(string $name = null): void
    {
        $name = $name ?: $this->getDefaultDriver();

        unset($this->mailers[$name]);
    }

    /**
     * Register a custom transport creator Closure.
     *
     * @param string $driver
     * @param \Closure $callback
     * @return $this
     */
    public function extend(string $driver, Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get the application instance used by the manager.
     *
     * @return App
     */
    public function getApplication(): App
    {
        return $this->app;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @param App $app
     * @return $this
     */
    public function setApplication(App $app): self
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Forget all of the resolved mailer instances.
     *
     * @return $this
     */
    public function forgetMailers(): self
    {
        $this->mailers = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->mailer()->$method(...$parameters);
    }
}
