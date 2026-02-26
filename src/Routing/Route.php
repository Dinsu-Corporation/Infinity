<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Routing;

use Dinsu\Infinity\Execution\RequestHandlerInterface;

/**
 * Route
 * * A value object representing a single registered endpoint in the system.
 * It maps an HTTP method and path to a specific RequestHandler.
 */
final class Route
{
    private readonly string $method;

    public function __construct(
        string $method,
        private readonly string $path,
        private readonly RequestHandlerInterface $handler,
    ) {
        $this->method = strtoupper(trim($method));
    }

    /**
     * Returns the HTTP verb (GET, POST, etc.)
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Returns the URI pattern for this route.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Returns the handler responsible for processing this route.
     */
    public function handler(): RequestHandlerInterface
    {
        return $this->handler;
    }
}
