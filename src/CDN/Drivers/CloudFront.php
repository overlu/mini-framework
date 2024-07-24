<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\CDN\Drivers;

use Aws\CloudFront\UrlSigner as AwsCloudFrontUrlSigner;
use Carbon\Carbon;
use DateTime;
use Mini\CDN\AbstractCDN;
use Mini\Exception\InvalidExpiration;

class CloudFront implements AbstractCDN
{
    private AwsCloudFrontUrlSigner $urlSigner;

    /**
     * @param AwsCloudFrontUrlSigner $urlSigner
     */
    public function __construct(AwsCloudFrontUrlSigner $urlSigner)
    {
        $this->urlSigner = $urlSigner;
    }

    /**
     * Get a secure URL to a controller action.
     *
     * @param string $url
     * @param DateTime|int|null $expiration
     * @param mixed|null $policy
     * @return string
     * @throws InvalidExpiration
     */
    public function sign(string $url, DateTime|int $expiration = null, mixed $policy = null): string
    {
        $expiration = $this->getExpirationTimestamp($expiration ??
            config('cdn.drivers.cloudfront.default_expiration_time_in_seconds'));

        return $this->urlSigner->getSignedUrl($url, $expiration, $policy);
    }

    /**
     * @param string $url
     * @param mixed|null $policy
     * @return string
     */
    public function url(string $url, mixed $policy = null): string
    {
        return $this->urlSigner->getSignedUrl($url, null, $policy);
    }

    /**
     * @param DateTime|int|null $expiration
     * @return int|null
     * @throws InvalidExpiration
     */
    protected function getExpirationTimestamp(DateTime|int $expiration = null): ?int
    {
        if (is_null($expiration)) {
            return null;
        }

        if (is_int($expiration)) {
            $expiration = Carbon::now()->addSeconds($expiration);
        }

        if (!$expiration instanceof DateTime) {
            throw new InvalidExpiration('Expiration must be an instance of DateTime or an integer');
        }


        if ($expiration->isPast()) {
            throw new InvalidExpiration('Expiration must be in the future');
        }

        return $expiration->getTimestamp();
    }
}
