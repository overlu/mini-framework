<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service;

use Closure;
use Mini\Command\AbstractCommandService;
use Mini\Command\CommandService;
use Mini\Container\Container;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Service\ServiceProviderInterface;
use Mini\Contracts\Support\DeferrableProvider;
use Mini\View\Compilers\BladeCompiler;
use Mini\View\Factory;
use Swoole\Server;
use function collect;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    /**
     * The application instance.
     *
     * @var Container
     */
    protected Container $app;


    /**
     * @var Server|null
     */
    protected ?Server $server;


    /**
     * @var int|null
     */
    protected ?int $worker_id;

    /**
     * All the registered booting callbacks.
     *
     * @var array
     */
    protected array $bootingCallbacks = [];

    /**
     * All the registered booted callbacks.
     *
     * @var array
     */
    protected array $bootedCallbacks = [];

    /**
     * The paths that should be published.
     *
     * @var array
     */
    public static array $publishes = [];

    /**
     * The paths that should be published by group.
     *
     * @var array
     */
    public static array $publishGroups = [];

    /**
     * Create a new service provider instance.
     *
     * @param Container $app
     * @param Server|null $server
     * @param int|null $worker_id
     */
    public function __construct(Container $app, ?Server $server = null, ?int $worker_id = null)
    {
        $this->app = $app;
        $this->server = $server;
        $this->worker_id = $worker_id;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     */
    abstract public function boot(): void;

    /**
     * Register a booting callback to be run before the "boot" method is called.
     *
     * @param Closure $callback
     * @return void
     */
    public function booting(Closure $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     *
     * @param Closure $callback
     * @return void
     */
    public function booted(Closure $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function callBootingCallbacks(): void
    {
        foreach ($this->bootingCallbacks as $callback) {
            $this->app->call($callback);
        }
    }

    /**
     * Call the registered booted callbacks.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function callBootedCallbacks(): void
    {
        foreach ($this->bootedCallbacks as $callback) {
            $this->app->call($callback);
        }
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param string $path
     * @param string $key
     * @return void
     * @throws BindingResolutionException
     */
    protected function mergeConfigFrom(string $path, string $key): void
    {
        $config = $this->app->make('config');

        $config->set($key, array_merge(
            require $path, $config->get($key, [])
        ));
    }

    /**
     * Register a view file namespace.
     *
     * @param string|array $path
     * @param string $namespace
     * @return void
     */
    protected function loadViewsFrom($path, string $namespace): void
    {
        $this->callAfterResolving('view', function (Factory $view) use ($path, $namespace) {
            if (isset($this->app->config['view']['paths']) &&
                is_array($this->app->config['view']['paths'])) {
                foreach ($this->app->config['view']['paths'] as $viewPath) {
                    if (is_dir($appPath = $viewPath . '/vendor/' . $namespace)) {
                        $view->addNamespace($namespace, $appPath);
                    }
                }
            }

            $view->addNamespace($namespace, $path);
        });
    }

    /**
     * Register the given view components with a custom prefix.
     *
     * @param string $prefix
     * @param array $components
     * @return void
     */
    protected function loadViewComponentsAs(string $prefix, array $components): void
    {
        $this->callAfterResolving(BladeCompiler::class, function ($blade) use ($prefix, $components) {
            foreach ($components as $alias => $component) {
                $blade->component($component, is_string($alias) ? $alias : null, $prefix);
            }
        });
    }

    /**
     * Register a translation file namespace.
     *
     * @param string $path
     * @param string $namespace
     * @return void
     */
    protected function loadTranslationsFrom(string $path, string $namespace): void
    {
        $this->callAfterResolving('translator', function ($translator) use ($path, $namespace) {
            $translator->addNamespace($namespace, $path);
        });
    }

    /**
     * Register a JSON translation file path.
     *
     * @param string $path
     * @return void
     */
    protected function loadJsonTranslationsFrom(string $path): void
    {
        $this->callAfterResolving('translator', function ($translator) use ($path) {
            $translator->addJsonPath($path);
        });
    }

    /**
     * Register database migration paths.
     *
     * @param array|string $paths
     * @return void
     */
    protected function loadMigrationsFrom(array|string $paths): void
    {
        $this->callAfterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array)$paths as $path) {
                $migrator->path($path);
            }
        });
    }

    /**
     * Setup an after resolving listener, or fire immediately if already resolved.
     *
     * @param string $name
     * @param Closure $callback
     * @return void
     */
    protected function callAfterResolving(string $name, Closure $callback): void
    {
        $this->app->afterResolving($name, $callback);

        if ($this->app->resolved($name)) {
            $callback($this->app->make($name), $this->app);
        }
    }

    /**
     * Register paths to be published by the publish command.
     *
     * @param array $paths
     * @param mixed|null $groups
     * @return void
     */
    protected function publishes(array $paths, mixed $groups = null): void
    {
        $this->ensurePublishArrayInitialized($class = static::class);

        static::$publishes[$class] = array_merge(static::$publishes[$class], $paths);

        foreach ((array)$groups as $group) {
            $this->addPublishGroup($group, $paths);
        }
    }

    /**
     * Ensure the publish array for the service provider is initialized.
     *
     * @param string $class
     * @return void
     */
    protected function ensurePublishArrayInitialized(string $class): void
    {
        if (!array_key_exists($class, static::$publishes)) {
            static::$publishes[$class] = [];
        }
    }

    /**
     * Add a publish group / tag to the service provider.
     *
     * @param string $group
     * @param array $paths
     * @return void
     */
    protected function addPublishGroup(string $group, array $paths): void
    {
        if (!array_key_exists($group, static::$publishGroups)) {
            static::$publishGroups[$group] = [];
        }

        static::$publishGroups[$group] = array_merge(
            static::$publishGroups[$group], $paths
        );
    }

    /**
     * Get the paths to publish.
     *
     * @param string|null $provider
     * @param string|null $group
     * @return array
     */
    public static function pathsToPublish(string $provider = null, string $group = null): array
    {
        if (!is_null($paths = static::pathsForProviderOrGroup($provider, $group))) {
            return $paths;
        }

        return collect(static::$publishes)->reduce(function ($paths, $p) {
            return array_merge($paths, $p);
        }, []);
    }

    /**
     * Get the paths for the provider or group (or both).
     *
     * @param string|null $provider
     * @param string|null $group
     * @return array|null
     */
    protected static function pathsForProviderOrGroup(?string $provider, ?string $group): ?array
    {
        if ($provider && $group) {
            return static::pathsForProviderAndGroup($provider, $group);
        }

        if ($group && array_key_exists($group, static::$publishGroups)) {
            return static::$publishGroups[$group];
        }

        if ($provider && array_key_exists($provider, static::$publishes)) {
            return static::$publishes[$provider];
        }

        if ($group || $provider) {
            return [];
        }
        return null;
    }

    /**
     * Get the paths for the provider and group.
     *
     * @param string $provider
     * @param string $group
     * @return array
     */
    protected static function pathsForProviderAndGroup(string $provider, string $group): array
    {
        if (!empty(static::$publishes[$provider]) && !empty(static::$publishGroups[$group])) {
            return array_intersect_key(static::$publishes[$provider], static::$publishGroups[$group]);
        }

        return [];
    }

    /**
     * Get the service providers available for publishing.
     *
     * @return array
     */
    public static function publishableProviders(): array
    {
        return array_keys(static::$publishes);
    }

    /**
     * Get the groups available for publishing.
     *
     * @return array
     */
    public static function publishableGroups(): array
    {
        return array_keys(static::$publishGroups);
    }

    /**
     * Register the package's custom Artisan commands.
     *
     * @param AbstractCommandService|AbstractCommandService[] $commands
     * @return void
     */
    public function commands(array|AbstractCommandService $commands): void
    {
        CommandService::register($commands);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when(): array
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred(): bool
    {
        return $this instanceof DeferrableProvider;
    }
}
