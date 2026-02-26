<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Http;

/**
 * Request
 * * Represents an immutable HTTP request.
 * Data is encapsulated to prevent global state pollution and ensure
 * that the request state remains consistent throughout the lifecycle.
 */
final class Request
{
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $headers,
        private readonly string $rawBody,
        private readonly mixed $parsedBody,
        private readonly array $routeParams,
    ) {}

    /**
     * Factory method to create a Request from PHP superglobals.
     * This is the entry point for converting the global state into an object.
     */
    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        $path = (string) parse_url($uri, PHP_URL_PATH);
        $normalizedPath = self::normalizePath($path);

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headers[str_replace('_', '-', strtolower($key))] = $value;
            }
        }

        $rawBody = file_get_contents('php://input') ?: '';
        $parsedBody = self::parseBody($headers['content-type'] ?? null, $rawBody);

        return new self(
            $method,
            $normalizedPath,
            $_GET,
            $headers,
            $rawBody,
            $parsedBody,
            [],
        );
    }

    /**
     * Returns the HTTP Method (GET, POST, etc.)
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Returns the cleaned URI path.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Returns the $_GET parameters.
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * Returns all normalized headers.
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Helper to get a specific header.
     */
    public function header(string $name, ?string $default = null): ?string
    {
        $normalizedName = strtolower($name);
        return $this->headers[$normalizedName] ?? $default;
    }

    /**
     * Returns the raw string from php://input.
     */
    public function rawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * Returns the parsed data (Array from JSON or Form-data).
     */
    public function parsedBody(): mixed
    {
        return $this->parsedBody;
    }

    /**
     * Returns all route parameters (e.g., ['id' => '123']).
     */
    public function routeParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Helper to get a specific route parameter.
     */
    public function routeParam(string $name, mixed $default = null): mixed
    {
        return $this->routeParams[$name] ?? $default;
    }

    /**
     * Returns a NEW instance with updated route parameters.
     * Essential for the Router to inject variables from the URL.
     */
    public function withRouteParams(array $routeParams): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->query,
            $this->headers,
            $this->rawBody,
            $this->parsedBody,
            $routeParams
        );
    }

    /**
     * Internal logic to decode JSON or Form Data based on Content-Type.
     */
    private static function parseBody(?string $contentType, string $rawBody): mixed
    {
        if ($rawBody === '') {
            return null;
        }

        $contentType = strtolower($contentType ?? '');

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($rawBody, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($rawBody, $parsedForm);
            return $parsedForm;
        }

        return $rawBody;
    }

    /**
     * Ensures paths are consistent (leading slash, no trailing slash).
     */
    private static function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/') {
            return '/';
        }
        return '/' . trim($path, '/');
    }
}
