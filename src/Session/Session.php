<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session;

use Exception;
use Mini\Contracts\HttpMessage\SessionInterface;
use Mini\Support\Arr;
use Mini\Support\Str;
use SessionHandlerInterface;

/**
 * This's a data class, please create an new instance for each requests.
 */
class Session implements SessionInterface
{
    use FlashTrait;

    /**
     * @var string
     */
    protected string $id;

    protected string $name;

    /**
     * @var array
     */
    protected array $attributes = [];

    /**
     * Session store started status.
     *
     * @var bool
     */
    protected bool $started = false;

    /**
     * @var SessionHandlerInterface
     */
    protected SessionHandlerInterface $handler;

    /**
     * Session constructor.
     * @param $name
     * @param SessionHandlerInterface $handler
     * @param null $id
     * @throws Exception
     */
    public function __construct($name, SessionHandlerInterface $handler, $id = null)
    {
        if (is_string($id) && $this->isValidId($id)) {
            $this->setId($id);
        }
        $this->setName($name);
        $this->handler = $handler;
    }

    /**
     * Determine if this is a valid session ID.
     * @param string $id
     * @return bool
     */
    public function isValidId(string $id): bool
    {
        return is_string($id) && ctype_alnum($id) && strlen($id) === 40;
    }

    /**
     * Starts the session storage.
     */
    public function start(): bool
    {
        $this->loadSession();

        return $this->started = true;
    }

    /**
     * Returns the session ID.
     *
     * @return string The session ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Sets the session ID.
     * @param string $id
     * @return Session
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Returns the session name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the session name.
     * @param string $name
     * @return Session
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Invalidates the current session.
     * Clears all session attributes and flashes and regenerates the
     * session and deletes the old session from persistence.
     *
     * @param int|null $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                      will leave the system settings unchanged, 0 sets the cookie
     *                      to expire with browser session. Time is in seconds, and is
     *                      not a Unix timestamp.
     * @return bool True if session invalidated, false if error
     * @throws Exception
     */
    public function invalidate(?int $lifetime = null): bool
    {
        $this->clear();

        return $this->migrate(true, $lifetime);
    }

    /**
     * Migrates the current session to a new session id while maintaining all
     * session attributes.
     *
     * @param bool $destroy Whether to delete the old session or leave it to garbage collection
     * @param int|null $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                      will leave the system settings unchanged, 0 sets the cookie
     *                      to expire with browser session. Time is in seconds, and is
     *                      not a Unix timestamp.
     * @return bool True if session migrated, false if error
     * @throws Exception
     */
    public function migrate(bool $destroy = false, ?int $lifetime = null): bool
    {
        if ($destroy) {
            $this->handler->destroy($this->getId());
        }

        $this->setId($this->generateSessionId());

        return true;
    }

    /**
     * Force the session to be saved and closed.
     * This method is generally not required for real sessions as
     * the session will be automatically saved at the end of
     * code execution.
     */
    public function save(): void
    {
        $this->ageFlashData();

        $this->handler->write($this->getId(), $this->prepareForStorage(serialize($this->attributes)));

        $this->started = false;
    }

    /**
     * Checks if an attribute is defined.
     *
     * @param string $name The attribute name
     * @return bool true if the attribute is defined, false otherwise
     */
    public function has(string $name): bool
    {
        return Arr::exists($this->attributes, $name);
    }

    /**
     * Returns an attribute.
     *
     * @param string $name The attribute name
     * @param mixed $default The default value if not found
     * @return array|\ArrayAccess|mixed
     */
    public function get(string $name, $default = null)
    {
        return Arr::get($this->attributes, $name, $default);
    }

    /**
     * Sets an attribute.
     *
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name, $value): void
    {
        Arr::set($this->attributes, $name, $value);
    }

    /**
     * Put a key / value pair or array of key / value pairs in the session.
     *
     * @param array|string $key
     * @param null|mixed $value
     */
    public function put($key, $value = null): void
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $arrayKey => $arrayValue) {
            $this->set($arrayKey, $arrayValue);
        }
    }

    /**
     * Returns attributes.
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Sets attributes.
     * @param array $attributes
     */
    public function replace(array $attributes): void
    {
        foreach ($attributes as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Removes an attribute, returning its value.
     *
     * @param string $name
     * @return mixed The removed value or null when it does not exist
     */
    public function remove(string $name)
    {
        return Arr::pull($this->attributes, $name);
    }

    /**
     * Remove one or many items from the session.
     *
     * @param array|string $keys
     */
    public function forget($keys): void
    {
        Arr::forget($this->attributes, $keys);
    }

    /**
     * Clears all attributes.
     */
    public function clear(): void
    {
        $this->attributes = [];
    }

    /**
     * Checks if the session was started.
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Get the CSRF token value.
     */
    public function token(): string
    {
        return (string)$this->get('_token');
    }

    /**
     * Regenerate the CSRF token value.
     * @return string
     * @throws Exception
     */
    public function regenerateToken(): string
    {
        $this->put('_token', $token = Str::random(40));
        return $token;
    }

    /**
     * Get the previous URL from the session.
     */
    public function previousUrl(): ?string
    {
        $previousUrl = $this->get('_previous.url');
        if (!is_string($previousUrl)) {
            $previousUrl = null;
        }
        return $previousUrl;
    }

    /**
     * Set the "previous" URL in the session.
     * @param string $url
     */
    public function setPreviousUrl(string $url): void
    {
        $this->set('_previous.url', $url);
    }

    /**
     * Push a value onto a session array.
     *
     * @param string $key
     * @param mixed $value
     */
    public function push(string $key, $value): void
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->put($key, $array);
    }

    /**
     * Generate a new random sessoion ID.
     * @return string
     * @throws Exception
     */
    protected function generateSessionId(): string
    {
        return Str::random(40);
    }


    /**
     * Load the session data from the handler.
     */
    protected function loadSession(): void
    {
        $this->attributes = array_merge($this->attributes, $this->readFromHandler());
    }

    /**
     * Read the session data from the handler.
     */
    protected function readFromHandler(): array
    {
        if ($data = $this->handler->read($this->getId())) {
            $data = @unserialize($this->prepareForUnserialize($data), false);

            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    /**
     * Prepare the raw string data from the session for unserialization.
     * @param string $data
     * @return string
     */
    protected function prepareForUnserialize(string $data): string
    {
        return $data;
    }

    /**
     * Prepare the serialized session data for storage.
     * @param string $data
     * @return string
     */
    protected function prepareForStorage(string $data): string
    {
        return $data;
    }
}
