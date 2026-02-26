<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Execution;

use Dinsu\Infinity\Http\Request;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): mixed;
}
