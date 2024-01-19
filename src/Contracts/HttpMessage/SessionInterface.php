<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\HttpMessage;

use RuntimeException;

interface SessionInterface
{
    /**
     * Starts the session storage.
     *
     * @return bool True if session started
     * @throws RuntimeException if session fails to start
     */
    public function start(): bool;

    /**
     * Returns the session ID.
     *
     * @return string The session ID
     */
    public function getId(): string;

    /**
     * Sets the session ID.
     * @param string $id
     */
    public function setId(string $id);

    /**
     * Returns the session name.
     */
    public function getName(): string;

    public function reset(): void;

    /**
     * Get the value of a given key and then forget it.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed;

    /**
     * Sets the session name.
     * @param string $name
     */
    public function setName(string $name);

    /**
     * Flush the session data and regenerate the ID.
     *
     * @return bool
     */
    public function invalidate(): bool;

    /**
     * Generate a new session ID for the session.
     *
     * @param bool $destroy
     * @return bool
     */
    public function migrate(bool $destroy = false): bool;

    /**
     * Force the session to be saved and closed.
     *
     * This method is generally not required for real sessions as
     * the session will be automatically saved at the end of
     * code execution.
     */
    public function save(): void;

    /**
     * Checks if a key is present and not null.
     *
     * @param array|string $key
     * @return bool
     */
    public function has(array|string $key): bool;

    /**
     * Returns an attribute.
     *
     * @param string $name The attribute name
     * @param mixed|null $default The default value if not found
     */
    public function get(string $name, mixed $default = null): mixed;

    /**
     * Sets an attribute.
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name, mixed $value): void;

    /**
     * Put a key / value pair or array of key / value pairs in the session.
     *
     * @param array|string $key
     * @param mixed|null $value
     */
    public function put(array|string $key, mixed $value = null): void;

    /**
     * Returns attributes.
     */
    public function all(): array;

    /**
     * Sets attributes.
     * @param array $attributes
     */
    public function replace(array $attributes): void;

    /**
     * Removes an attribute, returning its value.
     *
     * @param string $name
     * @return mixed The removed value or null when it does not exist
     */
    public function remove(string $name): mixed;

    /**
     * Remove one or many items from the session.
     *
     * @param array|string $keys
     */
    public function forget(array|string $keys): void;

    /**
     * Clears all attributes.
     */
    public function clear(): void;

    /**
     * Checks if the session was started.
     */
    public function isStarted(): bool;

    /**
     * Get the previous URL from the session.
     */
    public function previousUrl(): ?string;

    /**
     * Set the "previous" URL in the session.
     * @param string $url
     */
    public function setPreviousUrl(string $url): void;
}
