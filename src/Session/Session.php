<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session;

use Closure;
use Exception;
use Mini\Contracts\HttpMessage\SessionInterface;
use Mini\Contracts\Session\ExistenceAwareInterface;
use Mini\Support\Arr;
use Mini\Support\Str;
use SessionHandlerInterface;
use stdClass;

/**
 * This's a data class, please create an new instance for each requests.
 */
class Session implements SessionInterface
{
    use FlashTrait;

    /**
     * @var string
     */
    protected string $id = '';

    protected string $name = '';

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
        return ctype_alnum($id) && strlen($id) === 40;
    }

    /**
     * Starts the session storage.
     * @return bool
     * @throws Exception
     */
    public function start(): bool
    {
        $this->loadSession();

        if (!$this->has('_token')) {
            $this->regenerateToken();
        }

        return $this->started = true;
    }

    /**
     * Load the session data from the handler.
     */
    protected function loadSession(): void
    {
        $this->attributes = $this->readFromHandler();
    }

    /**
     * Read the session data from the handler.
     */
    protected function readFromHandler(): array
    {
        if ($data = $this->handler->read($this->getId())) {
            $data = @unserialize($this->prepareForUnserialize($data), ["allowed_classes" => true]);

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
     * Force the session to be saved and closed.
     * This method is generally not required for real sessions as
     * the session will be automatically saved at the end of
     * code execution.
     */
    public function save(): void
    {
        $this->ageFlashData();

        $this->handler->write($this->getId(), $this->prepareForStorage(serialize($this->attributes)));
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
     * @throws Exception
     */
    public function setId(string $id): self
    {
        $this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
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
     * Flush the session data and regenerate the ID.
     *
     * @return bool
     * @throws Exception
     */
    public function invalidate(): bool
    {
        $this->flush();

        return $this->migrate(true);
    }

    /**
     * Generate a new session ID for the session.
     *
     * @param bool $destroy
     * @return bool
     * @throws Exception
     */
    public function migrate(bool $destroy = false): bool
    {
        if ($destroy) {
            $this->handler->destroy($this->getId());
        }

        $this->setExists(false);

        $this->setId($this->generateSessionId());

        return true;
    }

    /**
     * Checks if a key exists.
     *
     * @param array|string $key
     * @return bool
     */
    public function exists(array|string $key): bool
    {
        $placeholder = new stdClass();

        return !collect(is_array($key) ? $key : func_get_args())->contains(function ($key) use ($placeholder) {
            return $this->get($key, $placeholder) === $placeholder;
        });
    }

    /**
     * Determine if the given key is missing from the session data.
     *
     * @param array|string $key
     * @return bool
     */
    public function missing(array|string $key): bool
    {
        return !$this->exists($key);
    }

    /**
     * Checks if an attribute is defined.
     *
     * @param array|string $key
     * @return bool true if the attribute is defined, false otherwise
     */
    public function has(array|string $key): bool
    {
        return !collect(is_array($key) ? $key : func_get_args())->contains(function ($key) {
            return is_null($this->get($key));
        });
    }

    /**
     * Returns an attribute.
     *
     * @param string $name The attribute name
     * @param mixed|null $default The default value if not found
     * @return array|\ArrayAccess|mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return Arr::get($this->attributes, $name, $default);
    }

    /**
     * Sets an attribute.
     *
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name, mixed $value): void
    {
        Arr::set($this->attributes, $name, $value);
    }

    /**
     * Put a key / value pair or array of key / value pairs in the session.
     *
     * @param array|string $key
     * @param mixed|null $value
     */
    public function put(array|string $key, mixed $value = null): void
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
     * Get a subset of the session data.
     *
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array
    {
        return Arr::only($this->attributes, $keys);
    }

    /**
     * Replace the given session attributes entirely.
     *
     * @param array $attributes
     * @return void
     */
    public function replace(array $attributes): void
    {
        $this->put($attributes);
    }

    /**
     * Removes an attribute, returning its value.
     *
     * @param string $name
     * @return mixed The removed value or null when it does not exist
     */
    public function remove(string $name): mixed
    {
        return Arr::pull($this->attributes, $name);
    }

    /**
     * Remove one or many items from the session.
     *
     * @param array|string $keys
     */
    public function forget(array|string $keys): void
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
     * Remove all of the items from the session.
     *
     * @return void
     */
    public function flush(): void
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
     * Generate a new session identifier.
     *
     * @param bool $destroy
     * @return bool
     * @throws Exception
     */
    public function regenerate(bool $destroy = false): bool
    {
        return tap($this->migrate($destroy), function () {
            $this->regenerateToken();
        });
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
     * Specify that the user has confirmed their password.
     *
     * @return void
     */
    public function passwordConfirmed(): void
    {
        $this->put('auth.password_confirmed_at', time());
    }

    /**
     * Get the underlying session handler implementation.
     *
     * @return SessionHandlerInterface
     */
    public function getHandler(): SessionHandlerInterface
    {
        return $this->handler;
    }

    /**
     * Push a value onto a session array.
     *
     * @param string $key
     * @param mixed $value
     */
    public function push(string $key, mixed $value): void
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
     * Get the value of a given key and then forget it.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        return Arr::pull($this->attributes, $key, $default);
    }

    /**
     * Determine if the session contains old input.
     *
     * @param string|null $key
     * @return bool
     */
    public function hasOldInput(?string $key = null): bool
    {
        $old = $this->getOldInput($key);

        return is_null($key) ? count($old) > 0 : !is_null($old);
    }

    /**
     * Get the requested item from the flashed input array.
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getOldInput(?string $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->get('_old_input', []), $key, $default);
    }

    /**
     * Get an item from the session, or store the default value.
     *
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    public function remember(string $key, Closure $callback): mixed
    {
        if (!is_null($value = $this->get($key))) {
            return $value;
        }

        return tap($callback(), function ($value) use ($key) {
            $this->put($key, $value);
        });
    }

    /**
     * Increment the value of an item in the session.
     *
     * @param string $key
     * @param int $amount
     * @return int
     */
    public function increment(string $key, int $amount = 1): int
    {
        $this->put($key, $value = (int)$this->get($key, 0) + $amount);

        return $value;
    }

    /**
     * Decrement the value of an item in the session.
     *
     * @param string $key
     * @param int $amount
     * @return int
     */
    public function decrement(string $key, int $amount = 1): int
    {
        return $this->increment($key, $amount * -1);
    }

    /**
     * Set the existence of the session on the handler if applicable.
     *
     * @param bool $value
     * @return void
     */
    public function setExists(bool $value): void
    {
        if ($this->handler instanceof ExistenceAwareInterface) {
            $this->handler->setExists($value);
        }
    }

    public function reset(): void
    {
        $this->id = '';
        $this->started = false;
        $this->attributes = [];
    }
}
