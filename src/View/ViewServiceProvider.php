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
     * @throws BindingResolutionException
     */
    public function registerFactory(): void
    {
        $app = app();
        $app->alias(Factory::class, 'view');
        /*$app->bind(Factory::class, function () {
            return new Factory($this->resolver, $this->viewFinder, app('events'));
        });*/
        $app->singleton(Factory::class, Factory::class, $this->resolver, $this->viewFinder, app('events'));
    }

    /**
     * Register the view finder implementation.
     */
    public function registerViewFinder(): void
    {
        $this->viewFinder = new FileViewFinder(app('files'), config('view.paths'));
    }

    /**
     * Register the Blade compiler implementation.
     */
    public function registerBladeCompiler(): void
    {
        $this->bladeCompiler = tap(new BladeCompiler(app('files'), config('view.compiled')), static function (BladeCompiler $blade) {
            $blade->component('dynamic-component', DynamicComponent::class);
        });
    }

    /**
     * Register the engine resolver instance.
     */
    public function registerEngineResolver(): void
    {
        $this->resolver = new EngineResolver;

        foreach (['file', 'php', 'blade'] as $engine) {
            $this->{'register' . ucfirst($engine) . 'Engine'}();
        }
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
            return new CompilerEngine($this->bladeCompiler, app('files'));
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
