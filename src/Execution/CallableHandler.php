<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Execution; // Updated to Framework Namespace

use Closure;
use Dinsu\Infinity\Http\Request;
use Dinsu\Infinity\Http\Response;

/**
 * CallableHandler
 * * Bridges the gap between a simple PHP callable and the formal
 * RequestHandlerInterface. This allows the Kernel to execute
 * controller methods as if they were standalone handler objects.
 */
final class CallableHandler implements RequestHandlerInterface
{
    /** * @var Closure(Request): Response
     */
    private readonly Closure $handler;

    /** * @param callable(Request): Response $handler
     */
    public function __construct(callable $handler)
    {
        $this->handler = Closure::fromCallable($handler);
    }

    /**
     * Executes the bridged callable and returns a standardized Response.
     */
    public function handle(Request $request): Response
    {
        return ($this->handler)($request);
    }
}
