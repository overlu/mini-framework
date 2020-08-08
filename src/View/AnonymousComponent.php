<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

class AnonymousComponent extends Component
{
    /**
     * The component view.
     *
     * @var string
     */
    protected string $view;

    /**
     * The component data.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Create a new class-less component instance.
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    public function __construct($view, $data)
    {
        $this->view = $view;
        $this->data = $data;
    }

    /**
     * Get the view / view contents that represent the component.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->view;
    }

    /**
     * Get the data that should be supplied to the view.
     *
     * @return array
     */
    public function data(): array
    {
        $this->attributes = $this->attributes ?: new ComponentAttributeBag;

        return $this->data + ['attributes' => $this->attributes];
    }
}
