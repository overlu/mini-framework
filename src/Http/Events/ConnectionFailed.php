<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http\Events;

use Mini\Http\Request;

class ConnectionFailed
{
    /**
     * The request instance.
     *
     * @var Request
     */
    public Request $request;

    /**
     * Create a new event instance.
     *
     * @param Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
