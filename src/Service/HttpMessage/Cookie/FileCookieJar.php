<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\HttpMessage\Cookie;

use RuntimeException;

/**
 * Persists non-session cookies using a JSON formatted file.
 */
class FileCookieJar extends CookieJar
{
    /** @var string filename */
    private string $filename;

    /** @var bool Control whether to persist session cookies or not. */
    private bool $storeSessionCookies;

    /**
     * Create a new FileCookieJar object.
     *
     * @param string $cookieFile File to store the cookie data
     * @param bool $storeSessionCookies set to true to store session cookies
     *                                  in the cookie jar
     *
     * @throws RuntimeException if the file cannot be found or created
     */
    public function __construct($cookieFile, $storeSessionCookies = false)
    {
        $this->filename = $cookieFile;
        $this->storeSessionCookies = $storeSessionCookies;

        if (file_exists($cookieFile)) {
            $this->load($cookieFile);
        }
    }

    /**
     * Saves the file when shutting down.
     */
    public function __destruct()
    {
        $this->save($this->filename);
    }

    /**
     * Saves the cookies to a file.
     *
     * @param string $filename File to save
     * @throws RuntimeException if the file cannot be found or created
     */
    public function save($filename)
    {
        $json = [];
        foreach ($this as $cookie) {
            /** @var SetCookie $cookie */
            if (CookieJar::shouldPersist($cookie, $this->storeSessionCookies)) {
                $json[] = $cookie->toArray();
            }
        }

        $jsonStr = json_encode($json);
        if (file_put_contents($filename, $jsonStr) === false) {
            throw new RuntimeException("Unable to save file {$filename}");
        }
    }

    /**
     * Load cookies from a JSON formatted file.
     *
     * Old cookies are kept unless overwritten by newly loaded ones.
     *
     * @param string $filename cookie file to load
     * @throws RuntimeException if the file cannot be loaded
     */
    public function load($filename)
    {
        $json = file_get_contents($filename);
        if ($json === false) {
            throw new RuntimeException("Unable to load file {$filename}");
        }
        if ($json === '') {
            return;
        }

        $data = json_decode($json, true);
        if (is_array($data)) {
            foreach (json_decode($json, true) as $cookie) {
                $this->setCookie(new SetCookie($cookie));
            }
        } elseif (strlen($data)) {
            throw new RuntimeException("Invalid cookie file: {$filename}");
        }
    }
}
