<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Support;

use Mini\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param Request $request
     * @return Response
     */
    public function toResponse(Request $request): Response;
}
