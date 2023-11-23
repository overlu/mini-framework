<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Service\AbstractServiceProvider;
use Mini\View\Compilers\BladeCompiler;
use Mini\View\Engines\CompilerEngine;
use Mini\View\Engines\EngineResolver;
use Mini\View\Engines\FileEngine;
use Mini\View\Engines\PhpEngine;

class ViewServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Register the view environment.
     *
     * @return void
     * @throws BindingResolutionException|\ReflectionException
     */
    public function registerFactory(): void
    {
        $this->app->singleton('view', function ($app) {
            $factory = new Factory($app['view.engine.resolver'], $app['view.finder'], $app['events']);
            $factory->setContainer($app);
            $factory->share('app', $app);
            return $factory;
        });
    }

    /**
     * Register the view finder implementation.
     * @throws BindingResolutionException
     */
    public function registerViewFinder(): void
    {
        $this->app->bind('view.finder', function ($app) {
            return new FileViewFinder($app['files'], config('view.paths'));
        });
    }

    /**
     * Register the Blade compiler implementation.
     * @throws BindingResolutionException
     */
    public function registerBladeCompiler(): void
    {
        $this->app->singleton('blade.compiler', function ($app) {
            return tap(new BladeCompiler($app['files'], config('view.compiled')), static function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }

    /**
     * Register the engine resolver instance.
     * @throws BindingResolutionException
     */
    public function registerEngineResolver(): void
    {
        $this->app->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;

            foreach (['file', 'php', 'blade'] as $engine) {
                $this->{'register' . ucfirst($engine) . 'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the file engine implementation.
     *
     * @param EngineResolver $resolver
     * @return void
     */
    public function registerFileEngine(EngineResolver $resolver): void
    {
        $resolver->register('file', function () {
            return new FileEngine($this->app['files']);
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param EngineResolver $resolver
     * @return void
     */
    public function registerPhpEngine(EngineResolver $resolver): void
    {
        $resolver->register('php', function () {
            return new PhpEngine($this->app['files']);
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param EngineResolver $resolver
     * @return void
     */
    public function registerBladeEngine(EngineResolver $resolver): void
    {
        $resolver->register('blade', function () {
            return new CompilerEngine($this->app['blade.compiler'], $this->app['files']);
        });
    }

    /**
     * @inheritDoc
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerViewFinder();
        $this->registerBladeCompiler();
        $this->registerEngineResolver();
        $this->registerFactory();
    }
}
