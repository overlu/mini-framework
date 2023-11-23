<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\HttpMessage\Cookie;

use Mini\Facades\Session;

/**
 * Persists cookies in the client session.
 */
class SessionCookieJar extends CookieJar
{
    /**
     * @var string session key
     */
    private string $sessionKey;

    /**
     * @var bool control whether to persist session cookies or not
     */
    private bool $storeSessionCookies;

    /**
     * Create a new SessionCookieJar object.
     *
     * @param string $sessionKey Session key name to store the cookie
     *                           data in session
     * @param bool $storeSessionCookies set to true to store session cookies
     *                                  in the cookie jar
     */
    public function __construct($sessionKey, $storeSessionCookies = false)
    {
        $this->sessionKey = $sessionKey;
        $this->storeSessionCookies = $storeSessionCookies;
        $this->load();
    }

    /**
     * Saves cookies to session when shutting down.
     */
    public function __destruct()
    {
        $this->save();
    }

    /**
     * Save cookies to the client session.
     */
    public function save()
    {
        $json = [];
        foreach ($this as $cookie) {
            /** @var SetCookie $cookie */
            if (CookieJar::shouldPersist($cookie, $this->storeSessionCookies)) {
                $json[] = $cookie->toArray();
            }
        }

        Session::set($this->sessionKey, json_encode($json));
    }

    /**
     * Load the contents of the client session into the data array.
     */
    protected function load()
    {
        if (!Session::exists($this->sessionKey)) {
            return;
        }
        $data = json_decode(Session::get($this->sessionKey), true);
        if (is_array($data)) {
            foreach ($data as $cookie) {
                $this->setCookie(new SetCookie($cookie));
            }
        } elseif (strlen($data)) {
            throw new \RuntimeException('Invalid cookie data');
        }
    }
}
