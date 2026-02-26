<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Routing\Exception;

use RuntimeException;
use Throwable;

/**
 * MethodNotAllowedException
 * * Thrown when a route matches the requested URI, but does not
 * support the requested HTTP method (e.g., POSTing to a GET-only route).
 */
final class MethodNotAllowedException extends RuntimeException
{
    /**
     * @param array<string> $allowedMethods List of valid methods for the matched route (e.g., ['GET', 'POST']).
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        private readonly array $allowedMethods,
        int $code = 405,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'HTTP Method not allowed. Allowed methods for this path: %s',
            implode(', ', $this->allowedMethods)
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the list of methods that are actually supported by the matched route.
     * Useful for setting the 'Allow' header in the final Response.
     * * @return array<string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
