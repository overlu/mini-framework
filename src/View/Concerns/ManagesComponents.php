<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Concerns;

use Closure;
use Mini\Contracts\Support\Htmlable;
use Mini\Support\Arr;
use Mini\Support\HtmlString;
use Mini\View\View;
use InvalidArgumentException;

trait ManagesComponents
{
    /**
     * The components being rendered.
     *
     * @var array
     */
    protected array $componentStack = [];

    /**
     * The original data passed to the component.
     *
     * @var array
     */
    protected array $componentData = [];

    /**
     * The slot contents for the component.
     *
     * @var array
     */
    protected array $slots = [];

    /**
     * The names of the slots being rendered.
     *
     * @var array
     */
    protected array $slotStack = [];

    /**
     * Start a component rendering process.
     *
     * @param \Mini\View\View|\Mini\Contracts\Support\Htmlable|\Closure|string $view
     * @param array $data
     * @return void
     */
    public function startComponent($view, array $data = []): void
    {
        if (ob_start()) {
            $this->componentStack[] = $view;

            $this->componentData[$this->currentComponent()] = $data;

            $this->slots[$this->currentComponent()] = [];
        }
    }

    /**
     * Get the first view that actually exists from the given list, and start a component.
     *
     * @param array $names
     * @param array $data
     * @return void
     */
    public function startComponentFirst(array $names, array $data = []): void
    {
        $name = Arr::first($names, function ($item) {
            return $this->exists($item);
        });

        $this->startComponent($name, $data);
    }

    /**
     * Render the current component.
     *
     * @return string
     * @throws \Throwable
     */
    public function renderComponent(): ?string
    {
        $view = array_pop($this->componentStack);

        $data = $this->componentData();

        if ($view instanceof Closure) {
            $view = $view($data);
        }

        if ($view instanceof View) {
            return $view->with($data)->render();
        }

        if ($view instanceof Htmlable) {
            return $view->toHtml();
        }

        return $this->make($view, $data)->render();
    }

    /**
     * Get the data for the given component.
     *
     * @return array
     */
    protected function componentData(): array
    {
        $defaultSlot = new HtmlString(trim(ob_get_clean()));

        $slots = array_merge([
            '__default' => $defaultSlot,
        ], $this->slots[count($this->componentStack)]);

        return array_merge(
            $this->componentData[count($this->componentStack)],
            ['slot' => $defaultSlot],
            $this->slots[count($this->componentStack)],
            ['__laravel_slots' => $slots],
        );
    }

    /**
     * Start the slot rendering process.
     *
     * @param string $name
     * @param string|null $content
     * @return void
     */
    public function slot(string $name, ?string $content = null): void
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('You passed too many arguments to the [' . $name . '] slot.');
        }

        if (func_num_args() === 2) {
            $this->slots[$this->currentComponent()][$name] = $content;
        } elseif (ob_start()) {
            $this->slots[$this->currentComponent()][$name] = '';

            $this->slotStack[$this->currentComponent()][] = $name;
        }
    }

    /**
     * Save the slot content for rendering.
     *
     * @return void
     */
    public function endSlot(): void
    {
        last($this->componentStack);

        $currentSlot = array_pop(
            $this->slotStack[$this->currentComponent()]
        );

        $this->slots[$this->currentComponent()]
        [$currentSlot] = new HtmlString(trim(ob_get_clean()));
    }

    /**
     * Get the index for the current component.
     *
     * @return int
     */
    protected function currentComponent(): int
    {
        return count($this->componentStack) - 1;
    }
}
