<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Http;

final class Response
{
    private array $headers = [];
    private string $content = '';
    private int $statusCode = 200;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        return new self(json_encode($data), $statusCode, $headers);
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = $value;
        return $new;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
        echo $this->content;
    }
}
