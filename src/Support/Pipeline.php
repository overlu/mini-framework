<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Closure;
use Psr\Container\ContainerInterface;

/**
 * This file mostly code come from Mini/pipe,
 * thanks Laravel Team provide such a useful class.
 */
class Pipeline
{
    /**
     * The container implementation.
     *
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected mixed $passable;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected array $pipes = [];

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected string $method = 'handle';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     * @param mixed $passable
     * @return Pipeline
     */
    public function send(mixed $passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param array|mixed $pipes
     * @return Pipeline
     */
    public function through(mixed $pipes): self
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }

    /**
     * Set the method to call on the pipes.
     * @param string $method
     * @return Pipeline
     */
    public function via(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     * @param Closure $destination
     * @return
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(array_reverse($this->pipes), $this->carry(), $this->prepareDestination($destination));

        return $pipeline($this->passable);
    }

    /**
     * Get the final piece of the Closure onion.
     * @param Closure $destination
     * @return Closure
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return static function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    // If the pipe is an instance of a Closure, we will just call it directly but
                    // otherwise we'll resolve the pipes out of the container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    return $pipe($passable, $stack);
                }
                if (!is_object($pipe)) {
                    [$name, $parameters] = $this->parsePipeString($pipe);

                    // If the pipe is a string we will parse the string and resolve the class out
                    // of the dependency injection container. We can then build a callable and
                    // execute the pipe function giving in the parameters that are required.
                    $pipe = $this->container->get($name);

                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    // If the pipe is already an object we'll just make a callable and pass it to
                    // the pipe as-is. There is no need to do any extra parsing and formatting
                    // since the object we're given was already a fully instantiated object.
                    $parameters = [$passable, $stack];
                }

                return method_exists($pipe, $this->method) ? $pipe->{$this->method}(...$parameters) : $pipe(...$parameters);
            };
        };
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param string $pipe
     * @return array
     */
    protected function parsePipeString(string $pipe): array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }
}
