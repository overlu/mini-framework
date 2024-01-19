<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

use Closure;
use Faker\Generator as Faker;
use Mini\Support\Traits\Macroable;
use InvalidArgumentException;

class FactoryBuilder
{
    use Macroable;

    /**
     * The model definitions in the container.
     *
     * @var array
     */
    protected array $definitions = [];

    /**
     * The model being built.
     *
     * @var string
     */
    protected string $class = '';

    /**
     * The database connection on which the model instance should be persisted.
     *
     * @var string
     */
    protected string $connection = '';

    /**
     * The model states.
     *
     * @var array
     */
    protected array $states;

    /**
     * The model after making callbacks.
     *
     * @var array
     */
    protected array $afterMaking = [];

    /**
     * The model after creating callbacks.
     *
     * @var array
     */
    protected array $afterCreating = [];

    /**
     * The states to apply.
     *
     * @var array
     */
    protected array $activeStates = [];

    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected Faker $faker;

    /**
     * The number of models to build.
     *
     * @var int|null
     */
    protected ?int $amount = null;

    /**
     * Create an new builder instance.
     *
     * @param string $class
     * @param array $definitions
     * @param array $states
     * @param array $afterMaking
     * @param array $afterCreating
     * @param \Faker\Generator $faker
     * @return void
     */
    public function __construct($class, array $definitions, array $states,
                                array $afterMaking, array $afterCreating, Faker $faker)
    {
        $this->class = $class;
        $this->faker = $faker;
        $this->states = $states;
        $this->definitions = $definitions;
        $this->afterMaking = $afterMaking;
        $this->afterCreating = $afterCreating;
    }

    /**
     * Set the amount of models you wish to create / make.
     *
     * @param int $amount
     * @return $this
     */
    public function times(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set the state to be applied to the model.
     *
     * @param string $state
     * @return $this
     */
    public function state(string $state): self
    {
        return $this->states([$state]);
    }

    /**
     * Set the states to be applied to the model.
     *
     * @param array|mixed $states
     * @return $this
     */
    public function states(mixed $states): self
    {
        $this->activeStates = is_array($states) ? $states : func_get_args();

        return $this;
    }

    /**
     * Set the database connection on which the model instance should be persisted.
     *
     * @param string $name
     * @return $this
     */
    public function connection(string $name): self
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Create a model and persist it in the database if requested.
     *
     * @param array $attributes
     * @return Closure
     */
    public function lazy(array $attributes = []): callable
    {
        return function () use ($attributes) {
            return $this->create($attributes);
        };
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param array $attributes
     * @return Collection|Model|mixed
     */
    public function create(array $attributes = []): mixed
    {
        $results = $this->make($attributes);

        if ($results instanceof Model) {
            $this->store(collect([$results]));

            $this->callAfterCreating(collect([$results]));
        } else {
            $this->store($results);

            $this->callAfterCreating($results);
        }

        return $results;
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param iterable $records
     * @return Collection|mixed
     */
    public function createMany(iterable $records): mixed
    {
        return (new $this->class)->newCollection(array_map(function ($attribute) {
            return $this->create($attribute);
        }, $records));
    }

    /**
     * Set the connection name on the results and store them.
     *
     * @param \Mini\Support\Collection $results
     * @return void
     */
    protected function store(\Mini\Support\Collection $results): void
    {
        $results->each(function ($model) {
            if (!isset($this->connection)) {
                $model->setConnection($model->newQueryWithoutScopes()->getConnection()->getName());
            }

            $model->save();
        });
    }

    /**
     * Create a collection of models.
     *
     * @param array $attributes
     * @return Collection|Model|mixed
     */
    public function make(array $attributes = []): mixed
    {
        if ($this->amount === null) {
            return tap($this->makeInstance($attributes), function ($instance) {
                $this->callAfterMaking(collect([$instance]));
            });
        }

        if ($this->amount < 1) {
            return (new $this->class)->newCollection();
        }

        $instances = (new $this->class)->newCollection(array_map(function () use ($attributes) {
            return $this->makeInstance($attributes);
        }, range(1, $this->amount)));

        $this->callAfterMaking($instances);

        return $instances;
    }

    /**
     * Create an array of raw attribute arrays.
     *
     * @param array $attributes
     * @return mixed
     */
    public function raw(array $attributes = []): mixed
    {
        if ($this->amount === null) {
            return $this->getRawAttributes($attributes);
        }

        if ($this->amount < 1) {
            return [];
        }

        return array_map(function () use ($attributes) {
            return $this->getRawAttributes($attributes);
        }, range(1, $this->amount));
    }

    /**
     * Get a raw attributes array for the model.
     *
     * @param array $attributes
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    protected function getRawAttributes(array $attributes = []): mixed
    {
        if (!isset($this->definitions[$this->class])) {
            throw new InvalidArgumentException("Unable to locate factory for [{$this->class}].");
        }

        $definition = call_user_func(
            $this->definitions[$this->class],
            $this->faker, $attributes
        );

        return $this->expandAttributes(
            array_merge($this->applyStates($definition, $attributes), $attributes)
        );
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param array $attributes
     * @return Model
     */
    protected function makeInstance(array $attributes = []): Model
    {
        return Model::unguarded(function () use ($attributes) {
            $instance = new $this->class(
                $this->getRawAttributes($attributes)
            );

            if (isset($this->connection)) {
                $instance->setConnection($this->connection);
            }

            return $instance;
        });
    }

    /**
     * Apply the active states to the model definition array.
     *
     * @param array $definition
     * @param array $attributes
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function applyStates(array $definition, array $attributes = []): array
    {
        foreach ($this->activeStates as $state) {
            if (!isset($this->states[$this->class][$state])) {
                if ($this->stateHasAfterCallback($state)) {
                    continue;
                }

                throw new InvalidArgumentException("Unable to locate [{$state}] state for [{$this->class}].");
            }

            $definition = array_merge(
                $definition,
                $this->stateAttributes($state, $attributes)
            );
        }

        return $definition;
    }

    /**
     * Get the state attributes.
     *
     * @param string $state
     * @param array $attributes
     * @return array
     */
    protected function stateAttributes(string $state, array $attributes): array
    {
        $stateAttributes = $this->states[$this->class][$state];

        if (!is_callable($stateAttributes)) {
            return $stateAttributes;
        }

        return $stateAttributes($this->faker, $attributes);
    }

    /**
     * Expand all attributes to their underlying values.
     *
     * @param array $attributes
     * @return array
     */
    protected function expandAttributes(array $attributes): array
    {
        foreach ($attributes as &$attribute) {
            if (is_callable($attribute) && !is_string($attribute) && !is_array($attribute)) {
                $attribute = $attribute($attributes);
            }

            if ($attribute instanceof static) {
                $attribute = $attribute->create()->getKey();
            }

            if ($attribute instanceof Model) {
                $attribute = $attribute->getKey();
            }
        }

        return $attributes;
    }

    /**
     * Run after making callbacks on a collection of models.
     *
     * @param \Mini\Support\Collection $models
     * @return void
     */
    public function callAfterMaking(\Mini\Support\Collection $models): void
    {
        $this->callAfter($this->afterMaking, $models);
    }

    /**
     * Run after creating callbacks on a collection of models.
     *
     * @param \Mini\Support\Collection $models
     * @return void
     */
    public function callAfterCreating(\Mini\Support\Collection $models): void
    {
        $this->callAfter($this->afterCreating, $models);
    }

    /**
     * Call after callbacks for each model and state.
     *
     * @param array $afterCallbacks
     * @param \Mini\Support\Collection $models
     * @return void
     */
    protected function callAfter(array $afterCallbacks, \Mini\Support\Collection $models): void
    {
        $states = array_merge(['default'], $this->activeStates);

        $models->each(function ($model) use ($states, $afterCallbacks) {
            foreach ($states as $state) {
                $this->callAfterCallbacks($afterCallbacks, $model, $state);
            }
        });
    }

    /**
     * Call after callbacks for each model and state.
     *
     * @param array $afterCallbacks
     * @param Model $model
     * @param string $state
     * @return void
     */
    protected function callAfterCallbacks(array $afterCallbacks, Model $model, string $state): void
    {
        if (!isset($afterCallbacks[$this->class][$state])) {
            return;
        }

        foreach ($afterCallbacks[$this->class][$state] as $callback) {
            $callback($model, $this->faker);
        }
    }

    /**
     * Determine if the given state has an "after" callback.
     *
     * @param string $state
     * @return bool
     */
    protected function stateHasAfterCallback(string $state): bool
    {
        return isset($this->afterMaking[$this->class][$state]) ||
            isset($this->afterCreating[$this->class][$state]);
    }
}
