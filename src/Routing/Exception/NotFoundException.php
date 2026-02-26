<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Routing\Exception;

use RuntimeException;
use Throwable;

/**
 * NotFoundException
 * * Thrown by the Router when no registered route matches the
 * requested URI path. This typically results in an HTTP 404 response.
 */
final class NotFoundException extends RuntimeException
{
    /**
     * @param string $message Custom message (e.g., "Route /api/users not found")
     * @param int $code Defaulted to 404 for HTTP compliance.
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = 'The requested resource was not found.',
        int $code = 404,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
