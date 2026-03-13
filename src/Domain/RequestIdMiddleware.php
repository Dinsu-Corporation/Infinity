<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Domain;

use Dinsu\Infinity\Execution\MiddlewareInterface;
use Dinsu\Infinity\Execution\RequestHandlerInterface;
use Dinsu\Infinity\Http\Request;
use Dinsu\Infinity\Http\Response;

final class RequestIdMiddleware implements MiddlewareInterface
{
    private const HEADER = 'x-request-id';

    public function process(Request $request, RequestHandlerInterface $next): mixed
    {
        $requestId = $request->header(self::HEADER) ?? $this->generateId();
        $result = $next->handle($request);

        if ($result instanceof Response) {
            return $result->withHeader(self::HEADER, $requestId);
        }

        return $result;
    }

    private function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
