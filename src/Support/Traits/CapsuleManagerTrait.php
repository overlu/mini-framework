<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

use Mini\Contracts\Container\Container;
use Mini\Support\Fluent;

trait CapsuleManagerTrait
{
    /**
     * The current globally used instance.
     *
     * @var object
     */
    protected static object $instance;

    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Setup the IoC container instance.
     *
     * @param Container $container
     * @return void
     */
    protected function setupContainer(Container $container): void
    {
        $this->container = $container;

        if (!$this->container->bound('config')) {
            $this->container->instance('config', new Fluent);
        }
    }

    /**
     * Make this capsule instance available globally.
     *
     * @return void
     */
    public function setAsGlobal(): void
    {
        static::$instance = $this;
    }

    /**
     * Get the IoC container instance.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * @param Container $container
     * @return void
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}
