<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Execution;

use Dinsu\Infinity\Http\Request;
use Dinsu\Infinity\Http\Response;

/**
 * RequestHandlerInterface
 * * The foundational contract for all Infinity components that
 * transform an HTTP Request into an HTTP Response.
 * * Every Controller, Middleware node, and Internal Handler
 * must implement this method to ensure interoperability.
 */
interface RequestHandlerInterface
{
    /**
     * Processes a request and produces a response.
     * * @param Request $request The incoming HTTP request.
     * @return Response The resulting HTTP response.
     */
    public function handle(Request $request): Response;
}
