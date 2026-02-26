<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Execution;

use Dinsu\Infinity\Http\Request;

interface RequestHandlerInterface
{
    public function handle(Request $request): mixed;
}
