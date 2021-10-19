<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Encryption;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Exception\MissingAppKeyException;
use Mini\Support\ServiceProvider;
use Mini\Support\Str;
use Opis\Closure\SerializableClosure;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->registerEncrypter();
        $this->registerOpisSecurityKey();
    }

    /**
     * Register the encrypter.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function registerEncrypter(): void
    {
        $this->app->singleton('encrypter', function ($app) {
            $config = config('app');

            return new Encrypter($this->parseKey($config), $config['cipher']);
        });
    }

    /**
     * Configure Opis Closure signing for security.
     *
     * @return void
     */
    protected function registerOpisSecurityKey(): void
    {
        $config = config('app');

        if (empty($config['key']) || !class_exists(SerializableClosure::class)) {
            return;
        }

        SerializableClosure::setSecretKey($this->parseKey($config));
    }

    /**
     * Parse the encryption key.
     *
     * @param array $config
     * @return string
     */
    protected function parseKey(array $config): string
    {
        if (Str::startsWith($key = $this->key($config), $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
        }

        return $key;
    }

    /**
     * Extract the encryption key from the given configuration.
     *
     * @param array $config
     * @return string
     *
     * @throws MissingAppKeyException
     */
    protected function key(array $config): string
    {
        if (empty($config['key'])) {
            throw new MissingAppKeyException;
        }
        return $config['key'];
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }
}
