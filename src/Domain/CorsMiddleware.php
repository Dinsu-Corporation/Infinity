<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Domain;

use Dinsu\Infinity\Execution\MiddlewareInterface;
use Dinsu\Infinity\Execution\RequestHandlerInterface;
use Dinsu\Infinity\Http\Request;
use Dinsu\Infinity\Http\Response;

final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $allowOrigin = '*',
        private readonly string $allowMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        private readonly string $allowHeaders = 'Content-Type, Authorization',
        private readonly string $maxAge = '86400'
    ) {}

    public function process(Request $request, RequestHandlerInterface $next): mixed
    {
        if ($request->method() === 'OPTIONS') {
            return $this->withCorsHeaders(new Response('', 204));
        }

        $result = $next->handle($request);

        if ($result instanceof Response) {
            return $this->withCorsHeaders($result);
        }

        return $result;
    }

    private function withCorsHeaders(Response $response): Response
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->allowOrigin)
            ->withHeader('Access-Control-Allow-Methods', $this->allowMethods)
            ->withHeader('Access-Control-Allow-Headers', $this->allowHeaders)
            ->withHeader('Access-Control-Max-Age', $this->maxAge);
    }
}
