<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\ServiceProviderInterface;
use Mini\View\Compilers\BladeCompiler;
use Mini\View\Engines\CompilerEngine;
use Mini\View\Engines\EngineResolver;
use Mini\View\Engines\FileEngine;
use Mini\View\Engines\PhpEngine;
use Swoole\Server;

class ViewServiceProvider implements ServiceProviderInterface
{
    /**
     * @var mixed
     */
    private $bladeCompiler;
    /**
     * @var FileViewFinder
     */
    private FileViewFinder $viewFinder;
    /**
     * @var EngineResolver
     */
    private EngineResolver $resolver;

    /**
     * Register the service provider.
     *
     * @param Server|null $server
     * @param int|null $workerId
     * @return void
     */
    public function register(?Server $server, ?int $workerId): void
    {
    }

    /**
     * Register the view environment.
     *
     * @return void
     */
    public function registerFactory(): void
    {
        $app = app();
        $app->alias(Factory::class, 'view');
        $app->singleton(Factory::class, function () {
            return new Factory(app('view.engine.resolver'), $this->viewFinder, app('events'));
        });
//        $app->singleton(Factory::class, Factory::class, $this->resolver, $this->viewFinder, app('events'));
    }

    /**
     * Register the view finder implementation.
     */
    public function registerViewFinder(): void
    {
        app()->bind('view.finder', function ($app) {
            return new FileViewFinder(app('files'), config('view.paths'));
        });
    }

    /**
     * Register the Blade compiler implementation.
     */
    public function registerBladeCompiler(): void
    {
        app()->singleton('blade.compiler', function ($app) {
            return tap(new BladeCompiler(app('files'), config('view.compiled')), static function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }

    /**
     * Register the engine resolver instance.
     */
    public function registerEngineResolver(): void
    {
        app()->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;

            // Next, we will register the various view engines with the resolver so that the
            // environment will resolve the engines needed for various views based on the
            // extension of view file. We call a method for each of the view's engines.
            foreach (['file', 'php', 'blade'] as $engine) {
                $this->{'register' . ucfirst($engine) . 'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the file engine implementation.
     *
     * @return void
     */
    public function registerFileEngine(): void
    {
        $this->resolver->register('file', static function () {
            return new FileEngine(app('files'));
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @return void
     */
    public function registerPhpEngine(): void
    {
        $this->resolver->register('php', static function () {
            return new PhpEngine(app('files'));
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @return void
     */
    public function registerBladeEngine(): void
    {
        $this->resolver->register('blade', function () {
            return new CompilerEngine(app('blade.compiler'), app('files'));
        });
    }

    /**
     * @inheritDoc
     * @throws BindingResolutionException
     */
    public function boot(?Server $server, ?int $workerId): void
    {
        $this->registerViewFinder();
        $this->registerBladeCompiler();
        $this->registerEngineResolver();
        $this->registerFactory();
    }
}
