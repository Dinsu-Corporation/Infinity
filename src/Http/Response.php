<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Http;

use JsonException;

/**
 * Response
 * * Represents an immutable HTTP response.
 * Once created, the state cannot be changed; instead, new instances are returned.
 */
final class Response
{
    public function __construct(
        private readonly int $status = 200,
        private readonly array $headers = [],
        private readonly string $body = '',
    ) {}

    /**
     * Factory for JSON responses.
     * Automatically sets Content-Type and handles JSON encoding errors.
     * * @throws JsonException
     */
    public static function json(array $payload, int $status = 200, array $headers = []): self
    {
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);

        $defaultHeaders = [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff'
        ];

        return new self(
            $status,
            array_merge($defaultHeaders, $headers),
            $encoded,
        );
    }

    /**
     * Factory for plain text responses.
     */
    public static function text(string $payload, int $status = 200, array $headers = []): self
    {
        return new self(
            $status,
            array_merge(['Content-Type' => 'text/plain; charset=utf-8'], $headers),
            $payload,
        );
    }

    /**
     * Returns a NEW instance with an additional or updated header.
     */
    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;

        return new self($this->status, $headers, $this->body);
    }

    /**
     * Sends the response to the output buffer.
     * This is the final step in the Request/Response lifecycle.
     */
    public function send(): void
    {
        if (headers_sent()) {
            echo $this->body;
            return;
        }

        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        echo $this->body;
    }
}
