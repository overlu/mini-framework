<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\CDN;

use Aws\CloudFront\UrlSigner;
use Mini\CDN\Drivers\CloudFront;
use Mini\Exception\InvalidKeyPairId;
use Mini\Service\AbstractServiceProvider;

class CDNServiceProvider extends AbstractServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(CDN::class, function () {
            return new CDN();
        });

        $this->app->alias(CDN::class, 'cdn');

        $this->app->singleton('cdn.drivers.cloudfront', function () {
            $config = config('cdn.drivers.cloudfront', []);

            if (empty($config['key_pair_id'])) {
                throw new InvalidKeyPairId('Cloudfront key pair id cannot be empty');
            }

            return new CloudFront(new UrlSigner($config['key_pair_id'], $config['private_key_path']));
        });
    }
}
