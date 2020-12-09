<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

trait AliasesTrait
{

    /** @var array */
    protected array $aliases = [];

    /**
     * Given $key and $alias to set alias
     * @param mixed $key
     * @param mixed $alias
     * @return static
     */
    public function setAlias(string $key, string $alias): self
    {
        $this->aliases[$key] = $alias;
        return $this;
    }

    /**
     * Given $aliases and set multiple aliases
     * @param array $aliases
     * @return static
     */
    public function setAliases(array $aliases): self
    {
        $this->aliases = array_merge($this->aliases, $aliases);
        return $this;
    }

    /**
     * Given alias from given $key
     * @param string $key
     * @return string
     */
    public function getAlias(string $key): string
    {
        return array_key_exists($key, $this->aliases) ? $this->aliases[$key] : $key;
    }

    /**
     * Get all $aliases
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * clear all $aliases
     */
    public function clearAliases(): void
    {
        $this->aliases = [];
    }
}
