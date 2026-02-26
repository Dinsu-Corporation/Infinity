<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Execution;

use Dinsu\Infinity\Http\Request;
use Dinsu\Infinity\Http\Response;

/**
 * MiddlewareStack
 * A wrapper that links a specific middleware to the rest of the handler chain.
 */
final class MiddlewareStack implements RequestHandlerInterface
{
    public function __construct(
        private readonly MiddlewareInterface $middleware,
        private readonly RequestHandlerInterface $next
    ) {}

    /**
     * Delegates handling to the middleware, passing the next handler in the stack.
     */
    public function handle(Request $request): Response
    {
        return $this->middleware->process($request, $this->next);
    }
}
