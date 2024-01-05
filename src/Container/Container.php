<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Container;

use ArrayAccess;
use Closure;
use Exception;
use InvalidArgumentException;
use Mini\Application;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Container\CircularDependencyException;
use Mini\Contracts\Container\Container as ContainerContract;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use TypeError;

class Container implements ArrayAccess, ContainerContract
{
    /**
     * The current globally available container (if any).
     */
    protected static ?Container $instance = null;

    /**
     * An array of the types that have been resolved.
     * @var bool[]
     */
    protected array $resolved = [];

    /**
     * The container's bindings.
     * @var array[]
     */
    protected array $bindings = [];

    /**
     * The container's method bindings.
     * @var Closure[]
     */
    protected array $methodBindings = [];

    /**
     * The container's shared instances.
     * @var object[]
     */
    protected array $instances = [];

    /**
     * The container's scoped instances.
     *
     * @var array
     */
    protected array $scopedInstances = [];

    /**
     * The registered type aliases.
     * @var string[]
     */
    protected array $aliases = [];

    /**
     * The registered aliases keyed by the abstract name.
     * @var array[]
     */
    protected array $abstractAliases = [];

    /**
     * The extension closures for services.
     * @var array[]
     */
    protected array $extenders = [];

    /**
     * All the registered tags.
     * @var array[]
     */
    protected array $tags = [];

    /**
     * The stack of concretions currently being built.
     * @var array[]
     */
    protected array $buildStack = [];

    /**
     * The parameter override stack.
     * @var array[]
     */
    protected array $with = [];

    /**
     * The contextual binding map.
     * @var array[]
     */
    public array $contextual = [];

    /**
     * All the registered rebound callbacks.
     * @var array[]
     */
    protected array $reboundCallbacks = [];

    /**
     * All of the global before resolving callbacks.
     *
     * @var \Closure[]
     */
    protected array $globalBeforeResolvingCallbacks = [];

    /**
     * All of the global resolving callbacks.
     *
     * @var \Closure[]
     */
    protected array $globalResolvingCallbacks = [];

    /**
     * All the global after resolving callbacks.
     * @var Closure[]
     */
    protected array $globalAfterResolvingCallbacks = [];

    /**
     * All of the before resolving callbacks by class type.
     *
     * @var array[]
     */
    protected array $beforeResolvingCallbacks = [];

    /**
     * All of the resolving callbacks by class type.
     *
     * @var array[]
     */
    protected array $resolvingCallbacks = [];

    /**
     * All the after resolving callbacks by class type.
     * @var array[]
     */
    protected array $afterResolvingCallbacks = [];

    /**
     * The application namespace.
     *
     * @var ?string
     */
    protected ?string $namespace = null;

    /**
     * Define a contextual binding.
     * @param array|string $concrete
     * @return \Mini\Contracts\Container\ContextualBindingBuilder
     */
    public function when($concrete)
    {
        $aliases = [];

        foreach (Util::arrayWrap($concrete) as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }

    /**
     * Determine if the given abstract type has been bound.
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->isAlias($abstract);
    }

    /**
     * @param $id
     * @return bool
     */
    public function has($id): bool
    {
        return $this->bound($id);
    }

    /**
     * Determine if the given abstract type has been resolved.
     * @param string $abstract
     * @return bool
     */
    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->resolved[$abstract]) ||
            isset($this->instances[$abstract]);
    }

    /**
     * Determine if a given type is shared.
     * @param string $abstract
     * @return bool
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Determine if a given string is an alias.
     * @param string $name
     * @return bool
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Register a binding with the container.
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     * @throws BindingResolutionException|ReflectionException
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
        if (!$concrete instanceof Closure) {
            if (!is_string($concrete)) {
                throw new TypeError(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }
    }

    /**
     * Get the Closure to be used when building a type.
     * @param string $abstract
     * @param string $concrete
     * @return Closure
     */
    protected function getClosure(string $abstract, string $concrete): callable
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve(
                $concrete, $parameters, $raiseEvents = false
            );
        };
    }

    /**
     * Determine if the container has a method binding.
     * @param string $method
     * @return bool
     */
    public function hasMethodBinding(string $method): bool
    {
        return isset($this->methodBindings[$method]);
    }

    /**
     * Bind a callback to resolve with Container::call.
     * @param array|string $method
     * @param Closure $callback
     * @return void
     */
    public function bindMethod($method, Closure $callback): void
    {
        $this->methodBindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * Get the method to be bound in class@method format.
     * @param array|string $method
     * @return string
     */
    protected function parseBindMethod($method): string
    {
        if (is_array($method)) {
            return $method[0] . '@' . $method[1];
        }
        return $method;
    }

    /**
     * Get the method binding for the given method.
     * @param string $method
     * @param mixed $instance
     * @return mixed
     */
    public function callMethodBinding(string $method, $instance)
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }

    /**
     * Add a contextual binding to the container.
     *
     * @param string $concrete
     * @param string $abstract
     * @param Closure|string $implementation
     * @return void
     */
    public function addContextualBinding(string $concrete, string $abstract, $implementation): void
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    /**
     * Register a binding if it hasn't already been registered.
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     * @throws BindingResolutionException|ReflectionException
     */
    public function bindIf(string $abstract, $concrete = null, bool $shared = false): void
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @return void
     * @throws BindingResolutionException|ReflectionException
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a shared binding if it hasn't already been registered.
     * @param string $abstract
     * @param null $concrete
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function singletonIf(string $abstract, $concrete = null): void
    {
        if (!$this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * Register a scoped binding in the container.
     *
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @return void
     */
    public function scoped(string $abstract, $concrete = null)
    {
        $this->scopedInstances[] = $abstract;

        $this->singleton($abstract, $concrete);
    }

    /**
     * Register a scoped binding if it hasn't already been registered.
     *
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @return void
     */
    public function scopedIf(string $abstract, $concrete = null)
    {
        if (!$this->bound($abstract)) {
            $this->scopedInstances[] = $abstract;

            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * "Extend" an abstract type in the container.
     * @param string $abstract
     * @param Closure $closure
     * @return void
     * @throws InvalidArgumentException
     * @throws BindingResolutionException|ReflectionException
     */
    public function extend(string $abstract, Closure $closure): void
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);

            $this->rebound($abstract);
        } else {
            $this->extenders[$abstract][] = $closure;

            if ($this->resolved($abstract)) {
                $this->rebound($abstract);
            }
        }
    }

    /**
     * Register an existing instance as shared in the container.
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    public function instance(string $abstract, $instance)
    {
        $this->removeAbstractAlias($abstract);

        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->rebound($abstract);
        }

        return $instance;
    }

    /**
     * Remove an alias from the contextual binding alias cache.
     * @param string $searched
     * @return void
     */
    protected function removeAbstractAlias(string $searched): void
    {
        if (!isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->abstractAliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias == $searched) {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }

    /**
     * Assign a set of tags to a given binding.
     * @param array|string $abstracts
     * @param array|mixed ...$tags
     * @return void
     */
    public function tag($abstracts, $tags): void
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);

        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array)$abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    /**
     * Resolve all the bindings for a given tag.
     * @param string $tag
     * @return iterable
     */
    public function tagged(string $tag): iterable
    {
        if (!isset($this->tags[$tag])) {
            return [];
        }

        return new RewindableGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract) {
                yield $this->make($abstract);
            }
        }, count($this->tags[$tag]));
    }

    /**
     * Alias a type to a different name.
     * @param string $abstract
     * @param string $alias
     * @return void
     *
     * @throws LogicException
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * Bind a new callback to an abstract's rebind event.
     * @param string $abstract
     * @param Closure $callback
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    public function rebinding(string $abstract, Closure $callback)
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }
        return null;
    }

    /**
     * Refresh an instance on the given target and method.
     * @param string $abstract
     * @param mixed $target
     * @param string $method
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    public function refresh(string $abstract, object $target, string $method)
    {
        return $this->rebinding($abstract, static function ($app, $instance) use ($target, $method) {
            $target->{$method}($instance);
        });
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     * @param string $abstract
     * @return void
     * @throws BindingResolutionException|ReflectionException
     */
    protected function rebound(string $abstract): void
    {
        $instance = $this->make($abstract);

        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            $callback($this, $instance);
        }
    }

    /**
     * Get the rebound callbacks for a given type.
     * @param string $abstract
     * @return array
     */
    protected function getReboundCallbacks(string $abstract): array
    {
        return $this->reboundCallbacks[$abstract] ?? [];
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     * @param Closure $callback
     * @param array $parameters
     * @return Closure
     */
    public function wrap(Closure $callback, array $parameters = []): callable
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     * @param callable|string $callback
     * @param array<string, mixed> $parameters
     * @param string|null $defaultMethod
     * @return mixed
     * @throws InvalidArgumentException|BindingResolutionException
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param string $abstract
     * @return Closure
     */
    public function factory(string $abstract): callable
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    /**
     * An alias function name for make().
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    public function makeWith(string $abstract, array $parameters = [])
    {
        return $this->make($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    public function make(string $abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * @param string $id
     * @return mixed
     * @throws EntryNotFoundException
     * @throws Exception
     */
    public function get(string $id)
    {
        try {
            return $this->resolve($id);
        } catch (Exception $e) {
            if ($this->has($id) || $e instanceof CircularDependencyException) {
                throw $e;
            }
            throw new EntryNotFoundException($id, $e->getCode(), $e);
        }
    }

    /**
     * Resolve the given type from the container.
     * @param string $abstract
     * @param array $parameters
     * @param bool $raiseEvents
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    protected function resolve(string $abstract, array $parameters = [], bool $raiseEvents = true)
    {
        $abstract = $this->getAlias($abstract);

        // First we'll fire any event handlers which handle the "before" resolving of
        // specific types. This gives some hooks the chance to add various extends
        // calls to change the resolution of objects that they're interested in.
        if ($raiseEvents) {
            $this->fireBeforeResolvingCallbacks($abstract, $parameters);
        }

        $concrete = $this->getContextualConcrete($abstract);

        $needsContextualBuild = !empty($parameters) || !is_null($concrete);

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$abstract]) && !$needsContextualBuild) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        if (is_null($concrete)) {
            $concrete = $this->getConcrete($abstract);
        }

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete);
        }

        // If we defined any extenders for this type, we'll need to spin through them
        // and apply them to the object being built. This allows for the extension
        // of services, such as changing configuration or decorating the object.
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract) && !$needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }

        if ($raiseEvents) {
            $this->fireResolvingCallbacks($abstract, $object);
        }

        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return the fully constructed class instance.
        $this->resolved[$abstract] = true;

        array_pop($this->with);

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param string $abstract
     * @return mixed
     */
    protected function getConcrete(string $abstract)
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Get the contextual concrete binding for the given abstract.
     * @param string $abstract
     * @return Closure|string|null
     */
    protected function getContextualConcrete(string $abstract)
    {
        if (!is_null($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }

        // Next we need to see if a contextual binding might be bound under an alias of the
        // given abstract type. So, we will need to check if any aliases exist with this
        // type and then spin through them and check for contextual bindings on these.
        if (empty($this->abstractAliases[$abstract])) {
            return null;
        }

        foreach ($this->abstractAliases[$abstract] as $alias) {
            if (!is_null($binding = $this->findInContextualBindings($alias))) {
                return $binding;
            }
        }
        return null;
    }

    /**
     * Find the concrete binding for the given abstract in the contextual binding array.
     * @param string $abstract
     * @return Closure|string|null
     */
    protected function findInContextualBindings(string $abstract)
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }

    /**
     * Determine if the given concrete is buildable.
     * @param mixed $concrete
     * @param string $abstract
     * @return bool
     */
    protected function isBuildable($concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
     * @param Closure|string $concrete
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    public function build($concrete)
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface or Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (!$reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        // if (in_array($concrete, $this->buildStack)) {
        //     throw new CircularDependencyException("Circular dependency detected while resolving [{$concrete}].");
        // }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (BindingResolutionException $e) {
            array_pop($this->buildStack);

            throw $e;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all the dependencies from the ReflectionParameters.
     * @param ReflectionParameter[] $dependencies
     * @return array
     * @throws BindingResolutionException|ReflectionException
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If this dependency has a override for this particular build we will use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);

                continue;
            }

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            $result = is_null(Util::getParameterClassName($dependency))
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);

            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Determine if the given dependency has a parameter override.
     * @param ReflectionParameter $dependency
     * @return bool
     */
    protected function hasParameterOverride(ReflectionParameter $dependency): bool
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    /**
     * Get a parameter override for a dependency.
     * @param ReflectionParameter $dependency
     * @return mixed
     */
    protected function getParameterOverride(ReflectionParameter $dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * Get the last parameter override.
     * @return array
     */
    protected function getLastParameterOverride(): array
    {
        return count($this->with) ? end($this->with) : [];
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if (!is_null($concrete = $this->getContextualConcrete('$' . $parameter->getName()))) {
            return $concrete instanceof Closure ? $concrete($this) : $concrete;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        $this->unresolvablePrimitive($parameter);
        return null;
    }

    /**
     * Resolve a class based dependency from the container.
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $parameter->isVariadic()
                ? $this->resolveVariadicClass($parameter)
                : $this->make(Util::getParameterClassName($parameter));
        }

            // If we can not resolve the class instance, we will check to see if the value
            // is optional, and if it is we will return the optional parameter value as
            // the value of the dependency, similarly to how we do this with scalars.
        catch (BindingResolutionException $e) {
            if ($parameter->isDefaultValueAvailable()) {
                array_pop($this->with);

                return $parameter->getDefaultValue();
            }

            if ($parameter->isVariadic()) {
                array_pop($this->with);

                return [];
            }

            throw $e;
        }
    }

    /**
     * Resolve a class based variadic dependency from the container.
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    protected function resolveVariadicClass(ReflectionParameter $parameter)
    {
        $className = Util::getParameterClassName($parameter);

        $abstract = $this->getAlias($className);

        if (!is_array($concrete = $this->getContextualConcrete($abstract))) {
            return $this->make($className);
        }

        return array_map(function ($abstract) {
            return $this->resolve($abstract);
        }, (array)$concrete);
    }

    /**
     * Throw an exception that the concrete is not instantiable.
     * @param string $concrete
     * @return void
     * @throws BindingResolutionException
     */
    protected function notInstantiable(string $concrete): void
    {
        if (!empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new BindingResolutionException($message);
    }

    /**
     * Throw an exception for an unresolvable primitive.
     * @param ReflectionParameter $parameter
     * @return void
     * @throws BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter): void
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * Register a new before resolving callback for all types.
     *
     * @param \Closure|string $abstract
     * @param \Closure|null $callback
     * @return void
     */
    public function beforeResolving($abstract, Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalBeforeResolvingCallbacks[] = $abstract;
        } else {
            $this->beforeResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new resolving callback.
     *
     * @param Closure|string $abstract
     * @param Closure|null $callback
     * @return void
     */
    public function resolving($abstract, ?Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if (is_null($callback) && $abstract instanceof Closure) {
            $this->globalResolvingCallbacks[] = $abstract;
        } else {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new after resolving callback for all types.
     *
     * @param Closure|string $abstract
     * @param Closure|null $callback
     * @return void
     */
    public function afterResolving($abstract, ?Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Fire all of the before resolving callbacks.
     *
     * @param string $abstract
     * @param array $parameters
     * @return void
     */
    protected function fireBeforeResolvingCallbacks(string $abstract, array $parameters = []): void
    {
        $this->fireBeforeCallbackArray($abstract, $parameters, $this->globalBeforeResolvingCallbacks);

        foreach ($this->beforeResolvingCallbacks as $type => $callbacks) {
            if ($type === $abstract || is_subclass_of($abstract, $type)) {
                $this->fireBeforeCallbackArray($abstract, $parameters, $callbacks);
            }
        }
    }

    /**
     * Fire an array of callbacks with an object.
     *
     * @param string $abstract
     * @param array $parameters
     * @param array $callbacks
     * @return void
     */
    protected function fireBeforeCallbackArray(string $abstract, array $parameters, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($abstract, $parameters, $this);
        }
    }

    /**
     * Fire all the resolving callbacks.
     * @param string $abstract
     * @param mixed $object
     * @return void
     */
    protected function fireResolvingCallbacks(string $abstract, $object): void
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
        );

        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    /**
     * Fire all the after resolving callbacks.
     *
     * @param string $abstract
     * @param mixed $object
     * @return void
     */
    protected function fireAfterResolvingCallbacks(string $abstract, $object): void
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }

    /**
     * Get all callbacks for a given type.
     *
     * @param string $abstract
     * @param object $object
     * @param array $callbacksPerType
     *
     * @return array
     */
    protected function getCallbacksForType(string $abstract, object $object, array $callbacksPerType): array
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || $object instanceof $type) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    /**
     * Fire an array of callbacks with an object.
     *
     * @param mixed $object
     * @param array $callbacks
     * @return void
     */
    protected function fireCallbackArray($object, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Get the container's bindings.
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * Get the extender callbacks for a given type.
     *
     * @param string $abstract
     * @return array
     */
    protected function getExtenders(string $abstract): array
    {
        $abstract = $this->getAlias($abstract);

        return $this->extenders[$abstract] ?? [];
    }

    /**
     * Remove all the extender callbacks for a given type.
     *
     * @param string $abstract
     * @return void
     */
    public function forgetExtenders(string $abstract): void
    {
        unset($this->extenders[$this->getAlias($abstract)]);
    }

    /**
     * Drop all the stale instances and aliases.
     *
     * @param string $abstract
     * @return void
     */
    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Remove a resolved instance from the instance cache.
     *
     * @param string $abstract
     * @return void
     */
    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Clear all the instances from the container.
     *
     * @return void
     */
    public function forgetInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Clear all of the scoped instances from the container.
     *
     * @return void
     */
    public function forgetScopedInstances(): void
    {
        foreach ($this->scopedInstances as $scoped) {
            unset($this->instances[$scoped]);
        }
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
        $this->scopedInstances = [];
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): Container
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param ContainerContract|null $container
     */
    public static function setInstance(ContainerContract $container = null): ?ContainerContract
    {
        return static::$instance = $container;
    }

    /**
     * Determine if a given offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->bound($offset);
    }

    /**
     * Get the value at a given offset.
     *
     * @param $offset
     * @return mixed
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function offsetGet($offset): mixed
    {
        return $this->make($offset);
    }

    /**
     * Set the value at a given offset.
     *
     * @param string $offset
     * @param mixed $value
     * @return void
     * @throws BindingResolutionException|ReflectionException
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->bind($offset, $value instanceof Closure ? $value : static function () use ($value) {
            return $value;
        });
    }

    /**
     * Unset the value at a given offset.
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->bindings[$offset], $this->instances[$offset], $this->resolved[$offset]);
    }

    /**
     * return mini framework version
     * @return string
     */
    public function version(): string
    {
        return Application::VERSION;
    }

    /**
     * @return string
     */
    public function environment(): string
    {
        return env('APP_ENV', 'local');
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        if (!is_null($this->namespace)) {
            return $this->namespace;
        }
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        foreach ((array)data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array)$path as $pathChoice) {
                if (realpath(base_path('app')) === realpath(base_path($pathChoice))) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new \RuntimeException('Unable to detect application namespace.');
    }

    /**
     * Dynamically access container services.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, $value)
    {
        $this[$key] = $value;
    }
}
