<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\HttpServer;

use Mini\Support\InteractsWithTime;
use Mini\Support\Str;
use Mini\Support\Traits\Macroable;
use Mini\Contracts\Routing\UrlGenerator as UrlGeneratorContract;

class UrlGenerator implements UrlGeneratorContract
{
    use InteractsWithTime, Macroable;

    /**
     * @var Request
     */
    protected $request;

    public function __construct()
    {
        $this->requesr = request();
    }

    /**
     * Get the full URL for the current request.
     *
     * @return string
     */
    public function full()
    {
        return $this->request->fullUrl();
    }

    /**
     * Get the current URL for the request.
     *
     * @return string
     */
    public function current()
    {
        return $this->to($this->request->getPathInfo());
    }

    /**
     * Get the URL for the previous request.
     *
     * @param mixed $fallback
     * @return string
     */
    public function previous($fallback = false)
    {
        $referrer = $this->request->header('referer');

        $url = $referrer ? $this->to($referrer) : '/';

        if ($url) {
            return $url;
        } elseif ($fallback) {
            return $this->to($fallback);
        }

        return $this->to('/');
    }

    /**
     * Generate an absolute URL to the given path.
     *
     * @param string $path
     * @param mixed $extra
     * @param bool|null $secure
     * @return string
     */
    public function to($path, $extra = [], $secure = null)
    {
        // First we will check if the URL is already a valid URL. If it is we will not
        // try to generate a new one but will simply return the URL as is, which is
        // convenient since developers do not always have to check if it's valid.
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $tail = implode('/', array_map(
                'rawurlencode', (array)$this->formatParameters($extra))
        );

        // Once we have the scheme we will compile the "tail" by collapsing the values
        // into a single string delimited by slashes. This just makes it convenient
        // for passing the array of parameters to this URL as a list of segments.
        $root = $this->formatRoot($this->formatScheme($secure));

        [$path, $query] = $this->extractQueryString($path);

        return $this->format(
                $root, '/' . trim($path . '/' . $tail, '/')
            ) . $query;
    }

    /**
     * Generate a secure, absolute URL to the given path.
     *
     * @param string $path
     * @param array $parameters
     * @return string
     */
    public function secure($path, $parameters = [])
    {
        return $this->to($path, $parameters, true);
    }

    /**
     * Generate the URL to an application asset.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    public function asset($path, $secure = null)
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
        $root = $this->assetRoot ?: $this->formatRoot($this->formatScheme($secure));

        return $this->removeIndex($root) . '/' . trim($path, '/');
    }

    /**
     * Generate the URL to a secure asset.
     *
     * @param string $path
     * @return string
     */
    public function secureAsset($path)
    {
        return $this->asset($path, true);
    }

    /**
     * Generate the URL to an asset from a custom root domain such as CDN, etc.
     *
     * @param string $root
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    public function assetFrom($root, $path, $secure = null)
    {
        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
        $root = $this->formatRoot($this->formatScheme($secure), $root);

        return $this->removeIndex($root) . '/' . trim($path, '/');
    }

    /**
     * Remove the index.php file from a path.
     *
     * @param string $root
     * @return string
     */
    protected function removeIndex(string $root, string $i = 'index.php')
    {
        return Str::contains($root, $i) ? str_replace('/' . $i, '', $root) : $root;
    }

    /**
     * Get the default scheme for a raw URL.
     *
     * @param bool|null $secure
     * @return string
     */
    public function formatScheme($secure = null)
    {
        if (!is_null($secure)) {
            return $secure ? 'https://' : 'http://';
        }

        if (is_null($this->cachedScheme)) {
            $this->cachedScheme = $this->forceScheme ?: $this->request->getScheme() . '://';
        }
        return $this->cachedScheme;
    }
}