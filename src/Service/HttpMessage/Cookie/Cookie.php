<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\HttpMessage\Cookie;

use DateTimeInterface;
use InvalidArgumentException;

class Cookie
{
    public const SAMESITE_LAX = 'lax';

    public const SAMESITE_STRICT = 'strict';

    protected string $name;

    protected string $value;

    protected ?string $domain;

    protected int $expire;

    protected string $path;

    protected bool $secure;

    protected bool $httpOnly;

    private bool $raw;

    private $sameSite;

    /**
     * @param string $name The name of the cookie
     * @param string $value The value of the cookie
     * @param int $expire The time the cookie expires
     * @param string $path The path on the server in which the cookie will be available on
     * @param string $domain The domain that the cookie is available to
     * @param bool $secure Whether the cookie should only be transmitted over a secure HTTPS connection from the client
     * @param bool $httpOnly Whether the cookie will be made accessible only through the HTTP protocol
     * @param bool $raw Whether the cookie value should be sent with no url encoding
     * @param null|string $sameSite Whether the cookie will be available for cross-site requests
     *
     */
    public function __construct(
        string $name,
        string $value = '',
        mixed $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        bool $raw = false,
        ?string $sameSite = null
    )
    {
        // from PHP source code
        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new InvalidArgumentException(sprintf('The cookie name "%s" contains invalid characters.', $name));
        }

        if (empty($name)) {
            throw new InvalidArgumentException('The cookie name cannot be empty.');
        }

        // convert expiration time to a Unix timestamp
        if ($expire instanceof DateTimeInterface) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $expire = strtotime((string)$expire);

            if ($expire === false) {
                throw new InvalidArgumentException('The cookie expiration time is not valid.');
            }
        }

        $this->name = $name;
        $this->value = (string)$value;
        $this->domain = $domain;
        $this->expire = 0 < $expire ? (int)$expire : 0;
        $this->path = empty($path) ? '/' : $path;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->raw = $raw;

        if ($sameSite !== null) {
            $sameSite = strtolower($sameSite);
        }

        if (!in_array($sameSite, [self::SAMESITE_LAX, self::SAMESITE_STRICT, null], true)) {
            throw new InvalidArgumentException('The "sameSite" parameter value is not valid.');
        }

        $this->sameSite = $sameSite;
    }

    /**
     * Returns the cookie as a string.
     *
     * @return string The cookie
     */
    public function __toString()
    {
        $str = ($this->isRaw() ? $this->getName() : urlencode($this->getName())) . '=';

        if ($this->getValue() === '') {
            $str .= 'deleted; expires=' . gmdate('D, d-M-Y H:i:s T', time() - 31536001) . '; max-age=-31536001';
        } else {
            $str .= $this->isRaw() ? $this->getValue() : rawurlencode($this->getValue());

            if ($this->getExpiresTime() !== 0) {
                $str .= '; expires=' . gmdate(
                        'D, d-M-Y H:i:s T',
                        $this->getExpiresTime()
                    ) . '; max-age=' . $this->getMaxAge();
            }
        }

        if ($this->getPath()) {
            $str .= '; path=' . $this->getPath();
        }

        if ($this->getDomain()) {
            $str .= '; domain=' . $this->getDomain();
        }

        if ($this->isSecure() === true) {
            $str .= '; secure';
        }

        if ($this->isHttpOnly() === true) {
            $str .= '; httponly';
        }

        if ($this->getSameSite() !== null) {
            $str .= '; samesite=' . $this->getSameSite();
        }

        return $str;
    }

    /**
     * Creates cookie from raw header string.
     *
     * @param string $cookie
     * @param bool $decode
     *
     * @return static
     */
    public static function fromString(string $cookie, bool $decode = false): Cookie
    {
        $data = [
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'raw' => !$decode,
            'samesite' => null,
        ];
        foreach (explode(';', $cookie) as $part) {
            if (!str_contains($part, '=')) {
                $key = trim($part);
                $value = true;
            } else {
                [$key, $value] = explode('=', trim($part), 2);
                $key = trim($key);
                $value = trim($value);
            }
            if (!isset($data['name'])) {
                $data['name'] = $decode ? urldecode($key) : $key;
                $data['value'] = $value === true ? null : ($decode ? urldecode($value) : $value);
                continue;
            }
            switch ($key = strtolower($key)) {
                case 'name':
                case 'value':
                    break;
                case 'max-age':
                    $data['expires'] = time() + (int)$value;
                    break;
                default:
                    $data[$key] = $value;
                    break;
            }
        }

        return new static($data['name'], $data['value'], $data['expires'], $data['path'], $data['domain'],
            $data['secure'], $data['httponly'], $data['raw'], $data['samesite']);
    }

    /**
     * Gets the name of the cookie.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the value of the cookie.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Gets the domain that the cookie is available to.
     *
     * @return null|string
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Gets the time the cookie expires.
     *
     * @return int
     */
    public function getExpiresTime(): int
    {
        return $this->expire;
    }

    /**
     * Gets the max-age attribute.
     *
     * @return int
     */
    public function getMaxAge(): int
    {
        return $this->expire !== 0 ? $this->expire - time() : 0;
    }

    /**
     * Gets the path on the server in which the cookie will be available on.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Checks whether the cookie should only be transmitted over a secure HTTPS connection from the client.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * Checks whether the cookie will be made accessible only through the HTTP protocol.
     *
     * @return bool
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * Whether this cookie is about to be cleared.
     *
     * @return bool
     */
    public function isCleared(): bool
    {
        return $this->expire < time();
    }

    /**
     * Checks if the cookie value should be sent with no url encoding.
     *
     * @return bool
     */
    public function isRaw(): bool
    {
        return $this->raw;
    }

    /**
     * Gets the SameSite attribute.
     *
     * @return null|string
     */
    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }
}
