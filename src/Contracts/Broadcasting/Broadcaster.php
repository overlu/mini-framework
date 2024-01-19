<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Broadcasting;

use Mini\Service\HttpServer\Request;

interface Broadcaster
{
    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param Request $request
     * @return mixed
     */
    public function auth(Request $request): mixed;

    /**
     * Return the valid authentication response.
     *
     * @param Request $request
     * @param mixed $result
     * @return mixed
     */
    public function validAuthenticationResponse(Request $request, mixed $result): mixed;

    /**
     * Broadcast the given event.
     *
     * @param array $channels
     * @param string $event
     * @param array $payload
     * @return void
     */
    public function broadcast(array $channels, string $event, array $payload = []): void;
}
