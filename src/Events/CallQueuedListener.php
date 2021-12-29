<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Events;

use Mini\Container\Container;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Queue\Job;
use Mini\Contracts\Queue\ShouldQueue;
use Mini\Queue\InteractsWithQueue;

class CallQueuedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The listener class name.
     *
     * @var string
     */
    public string $class;

    /**
     * The listener method.
     *
     * @var string
     */
    public string $method;

    /**
     * The data to be passed to the listener.
     *
     * @var array
     */
    public array $data;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $retryAfter;

    /**
     * The timestamp indicating when the job should timeout.
     *
     * @var int
     */
    public int $timeoutAt;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout;

    /**
     * Create a new job instance.
     *
     * @param string $class
     * @param string $method
     * @param array $data
     * @return void
     */
    public function __construct(string $class, string $method, array $data)
    {
        $this->data = $data;
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Handle the queued job.
     *
     * @param Container $container
     * @return void
     * @throws BindingResolutionException|\ReflectionException
     */
    public function handle(Container $container): void
    {
        $this->prepareData();

        $handler = $this->setJobInstanceIfNecessary(
            $this->job, $container->make($this->class)
        );

        call_user_func_array(
            [$handler, $this->method], $this->data
        );
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param \Mini\Contracts\Queue\Job $job
     * @param mixed $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {
        if (in_array(InteractsWithQueue::class, class_uses_recursive($instance), true)) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Call the failed method on the job instance.
     *
     * The event instance and the exception will be passed.
     *
     * @param \Throwable $e
     * @return void
     * @throws BindingResolutionException
     */
    public function failed($e): void
    {
        $this->prepareData();

        $handler = Container::getInstance()->make($this->class);

        $parameters = array_merge($this->data, [$e]);

        if (method_exists($handler, 'failed')) {
            call_user_func_array([$handler, 'failed'], $parameters);
        }
    }

    /**
     * Unserialize the data if needed.
     *
     * @return void
     */
    protected function prepareData(): void
    {
        if (is_string($this->data)) {
            $this->data = unserialize($this->data, ["allowed_classes" => true]);
        }
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName(): string
    {
        return $this->class;
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->data = array_map(function ($data) {
            return is_object($data) ? clone $data : $data;
        }, $this->data);
    }
}
