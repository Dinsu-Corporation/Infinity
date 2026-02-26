<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Domain;

use Dinsu\Infinity\Attribute\Service;

#[Service]
final class SystemService
{
    public function healthSnapshot(): array
    {
        return [
            'status' => 'ok',
            'framework' => 'Infinity',
            'php_version' => PHP_VERSION,
            'server_time' => gmdate('c'),
            'memory_usage' => sprintf('%0.2f MB', memory_get_usage() / 1024 / 1024),
        ];
    }

    public function welcomePayload(): array
    {
        return [
            'name' => 'Infinity Kernel',
            'version' => '1.0.0-alpha',
            'message' => 'The Infinity core engine is active and healthy.',
            'endpoints' => [
                'health_check' => '/health',
            ],
        ];
    }
}
