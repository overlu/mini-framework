<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

use ArrayAccess;
use Faker\Generator as Faker;
use Symfony\Component\Finder\Finder;

class Factory implements ArrayAccess
{
    /**
     * The model definitions in the container.
     *
     * @var array
     */
    protected array $definitions = [];

    /**
     * The registered model states.
     *
     * @var array
     */
    protected array $states = [];

    /**
     * The registered after making callbacks.
     *
     * @var array
     */
    protected array $afterMaking = [];

    /**
     * The registered after creating callbacks.
     *
     * @var array
     */
    protected array $afterCreating = [];

    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected Faker $faker;

    /**
     * Create a new factory instance.
     *
     * @param \Faker\Generator $faker
     * @return void
     */
    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Create a new factory container.
     *
     * @param \Faker\Generator $faker
     * @param string|null $pathToFactories
     * @return static
     */
    public static function construct(Faker $faker, ?string $pathToFactories = null)
    {
        $pathToFactories = $pathToFactories ?: database_path('factories');

        return (new static($faker))->load($pathToFactories);
    }

    /**
     * Define a class with a given set of attributes.
     *
     * @param string $class
     * @param callable $attributes
     * @return $this
     */
    public function define(string $class, callable $attributes): self
    {
        $this->definitions[$class] = $attributes;

        return $this;
    }

    /**
     * Define a state with a given set of attributes.
     *
     * @param string $class
     * @param string $state
     * @param callable|array $attributes
     * @return $this
     */
    public function state(string $class, string $state, $attributes): self
    {
        $this->states[$class][$state] = $attributes;

        return $this;
    }

    /**
     * Define a callback to run after making a model.
     *
     * @param string $class
     * @param callable $callback
     * @param string $name
     * @return $this
     */
    public function afterMaking(string $class, callable $callback, string $name = 'default'): self
    {
        $this->afterMaking[$class][$name][] = $callback;

        return $this;
    }

    /**
     * Define a callback to run after making a model with given state.
     *
     * @param string $class
     * @param string $state
     * @param callable $callback
     * @return $this
     */
    public function afterMakingState(string $class, string $state, callable $callback): self
    {
        return $this->afterMaking($class, $callback, $state);
    }

    /**
     * Define a callback to run after creating a model.
     *
     * @param string $class
     * @param callable $callback
     * @param string $name
     * @return $this
     */
    public function afterCreating(string $class, callable $callback, string $name = 'default'): self
    {
        $this->afterCreating[$class][$name][] = $callback;

        return $this;
    }

    /**
     * Define a callback to run after creating a model with given state.
     *
     * @param string $class
     * @param string $state
     * @param callable $callback
     * @return $this
     */
    public function afterCreatingState(string $class, string $state, callable $callback): self
    {
        return $this->afterCreating($class, $callback, $state);
    }

    /**
     * Create an instance of the given model and persist it to the database.
     *
     * @param string $class
     * @param array $attributes
     * @return mixed
     */
    public function create(string $class, array $attributes = [])
    {
        return $this->of($class)->create($attributes);
    }

    /**
     * Create an instance of the given model.
     *
     * @param string $class
     * @param array $attributes
     * @return mixed
     */
    public function make(string $class, array $attributes = [])
    {
        return $this->of($class)->make($attributes);
    }

    /**
     * Get the raw attribute array for a given model.
     *
     * @param string $class
     * @param array $attributes
     * @return array
     */
    public function raw(string $class, array $attributes = []): array
    {
        return array_merge(
            call_user_func($this->definitions[$class], $this->faker), $attributes
        );
    }

    /**
     * Create a builder for the given model.
     *
     * @param string $class
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\FactoryBuilder
=======
     * @return FactoryBuilder
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function of(string $class): FactoryBuilder
    {
        return new FactoryBuilder(
            $class, $this->definitions, $this->states,
            $this->afterMaking, $this->afterCreating, $this->faker
        );
    }

    /**
     * Load factories from path.
     *
     * @param string $path
     * @return $this
     */
    public function load(string $path): self
    {
        $factory = $this;

        if (is_dir($path)) {
            foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
                require $file->getRealPath();
            }
        }

        return $factory;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->definitions[$offset]);
    }

    /**
     * Get the value of the given offset.
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->make($offset);
    }

    /**
     * Set the given offset to the given value.
     *
     * @param string $offset
     * @param callable $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->define($offset, $value);
    }

    /**
     * Unset the value at the given offset.
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->definitions[$offset]);
    }
}
