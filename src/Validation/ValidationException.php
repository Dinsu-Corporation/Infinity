<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Validation;

use RuntimeException;
use Throwable;

final class ValidationException extends RuntimeException
{
    public function __construct(
        private readonly array $errors,
        string $message = 'Validation failed.',
        int $code = 400,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
