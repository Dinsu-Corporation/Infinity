<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Execution;

use Dinsu\Infinity\Http\Request;
use Dinsu\Infinity\Http\Response;

/**
 * MiddlewareInterface
 * Defines the contract for layers in the Infinity execution onion.
 */
interface MiddlewareInterface
{
    /**
     * Process an incoming request and return a response.
     */
    public function process(Request $request, RequestHandlerInterface $next): Response;
}
