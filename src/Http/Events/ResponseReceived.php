<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http\Events;


use Mini\Http\Request;
use Mini\Http\Response;

class ResponseReceived
{
    /**
     * The request instance.
     *
     * @var Request
     */
    public Request $request;

    /**
     * The response instance.
     *
     * @var Response
     */
    public Response $response;

    /**
     * Create a new event instance.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
