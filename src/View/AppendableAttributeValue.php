<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

class AppendableAttributeValue
{
    /**
     * The attribute value.
     *
     * @var mixed
     */
    public mixed $value;

    /**
     * Create a new appendable attribute value.
     *
     * @param mixed $value
     * @return void
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * Get the string value.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}
