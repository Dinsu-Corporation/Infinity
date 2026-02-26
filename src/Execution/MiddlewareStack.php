<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Execution;

use Dinsu\Infinity\Http\Request;

final class MiddlewareStack implements RequestHandlerInterface
{
    public function __construct(
        private readonly MiddlewareInterface $middleware,
        private readonly RequestHandlerInterface $next
    ) {}

    public function handle(Request $request): mixed
    {
        return $this->middleware->process($request, $this->next);
    }
}
