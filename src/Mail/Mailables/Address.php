<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail\Mailables;

class Address
{
    /**
     * The recipient's email address.
     *
     * @var string
     */
    public string $address;

    /**
     * The recipient's name.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Create a new address instance.
     *
     * @param string $address
     * @param string|null $name
     * @return void
     */
    public function __construct(string $address, string $name = null)
    {
        $this->address = $address;
        $this->name = $name;
    }
}
