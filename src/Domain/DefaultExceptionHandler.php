<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Domain;

use Dinsu\Infinity\Attribute\Intermediary;
use Dinsu\Infinity\Attribute\Handle;
use Dinsu\Infinity\Routing\Exception\NotFoundException;
use Dinsu\Infinity\Routing\Exception\MethodNotAllowedException;
use Dinsu\Infinity\Validation\ValidationException;
use Dinsu\Infinity\Http\Request;

#[Intermediary]
final class DefaultExceptionHandler
{
    #[Handle(NotFoundException::class)]
    public function handleNotFound(NotFoundException $e, Request $request): array
    {
        return [
            'timestamp' => gmdate('c'),
            'status' => 404,
            'error' => 'Not Found',
            'message' => $e->getMessage(),
            'path' => $request->path()
        ];
    }

    #[Handle(MethodNotAllowedException::class)]
    public function handleMethodNotAllowed(MethodNotAllowedException $e, Request $request): array
    {
        return [
            'timestamp' => gmdate('c'),
            'status' => 405,
            'error' => 'Method Not Allowed',
            'message' => $e->getMessage(),
            'path' => $request->path()
        ];
    }

    #[Handle(ValidationException::class)]
    public function handleValidation(ValidationException $e, Request $request): array
    {
        return [
            'timestamp' => gmdate('c'),
            'status' => 400,
            'error' => 'Bad Request',
            'message' => $e->getMessage(),
            'path' => $request->path(),
            'errors' => $e->errors()
        ];
    }
}
