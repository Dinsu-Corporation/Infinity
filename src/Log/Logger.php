<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Log;

final class Logger
{
    private const LEVELS = [
        'debug' => 100,
        'info' => 200,
        'notice' => 250,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
        'alert' => 550,
        'emergency' => 600,
    ];

    public function __construct(
        private readonly string $level = 'info',
        private readonly string $channel = 'app'
    ) {}

    public function debug(string $message, array $context = []): void { $this->log('debug', $message, $context); }
    public function info(string $message, array $context = []): void { $this->log('info', $message, $context); }
    public function notice(string $message, array $context = []): void { $this->log('notice', $message, $context); }
    public function warning(string $message, array $context = []): void { $this->log('warning', $message, $context); }
    public function error(string $message, array $context = []): void { $this->log('error', $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log('critical', $message, $context); }
    public function alert(string $message, array $context = []): void { $this->log('alert', $message, $context); }
    public function emergency(string $message, array $context = []): void { $this->log('emergency', $message, $context); }

    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        $minLevel = strtolower($this->level);

        $threshold = self::LEVELS[$minLevel] ?? self::LEVELS['info'];
        $current = self::LEVELS[$level] ?? self::LEVELS['info'];

        if ($current < $threshold) {
            return;
        }

        $timestamp = gmdate('c');
        $contextJson = $context === [] ? '' : ' ' . json_encode($context);
        $line = sprintf('[%s] %s.%s: %s%s', $timestamp, $this->channel, $level, $message, $contextJson);

        file_put_contents('php://stderr', $line . PHP_EOL);
    }
}
