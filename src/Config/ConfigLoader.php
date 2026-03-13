<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Config;

use RuntimeException;

/**
 * ConfigLoader
 * Handles hierarchical configuration loading with environment-specific overrides.
 * Optimized for "Package/Env" directory structures.
 */
final class ConfigLoader
{
    /**
     * Loads and merges configuration files.
     * * @param string $basePath The absolute path to the 'config' directory.
     * @param string $env The current environment (e.g., 'Dev', 'Prod').
     * @return array The merged configuration tree.
     */
    public static function load(string $basePath, string $env): array
    {
        $mainFile = $basePath . '/Application.yaml';

        $envFolder = ucfirst(strtolower($env));
        $envFile = sprintf('%s/Package/%s/Application.yaml', $basePath, $envFolder);

        $config = self::parse($mainFile);
        $envConfig = self::parse($envFile);

        $merged = array_replace_recursive($config, $envConfig);

        return self::applyEnvOverrides($merged, $_ENV);
    }

    /**
     * Parses a YAML file into a PHP array.
     * * @throws RuntimeException If the YAML extension is missing.
     */
    private static function parse(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        if (!function_exists('yaml_parse_file')) {
            throw new RuntimeException(
                "Critical Infrastructure Error: The PHP 'yaml' extension is required to process [$file]. " .
                "Please install 'libyaml' and enable 'extension=yaml' in your php.ini."
            );
        }

        $data = yaml_parse_file($file);

        return is_array($data) ? $data : [];
    }

    private static function applyEnvOverrides(array $config, array $env): array
    {
        foreach ($env as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }

            if (!str_starts_with($key, 'CONFIG__')) {
                continue;
            }

            $path = substr($key, 8);
            if ($path === '') {
                continue;
            }

            $segments = array_map(
                static fn (string $segment): string => strtolower($segment),
                array_filter(explode('__', $path), 'strlen')
            );

            if (empty($segments)) {
                continue;
            }

            $config = self::setByPath($config, $segments, self::castValue($value));
        }

        return $config;
    }

    private static function setByPath(array $config, array $segments, mixed $value): array
    {
        $ref =& $config;
        $lastIndex = count($segments) - 1;

        foreach ($segments as $index => $segment) {
            if ($index === $lastIndex) {
                $ref[$segment] = $value;
                break;
            }

            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }

            $ref =& $ref[$segment];
        }

        return $config;
    }

    private static function castValue(string $value): mixed
    {
        $trimmed = trim($value);
        $lower = strtolower($trimmed);

        if ($lower === 'true') {
            return true;
        }

        if ($lower === 'false') {
            return false;
        }

        if ($lower === 'null') {
            return null;
        }

        if (is_numeric($trimmed)) {
            return $trimmed + 0;
        }

        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $trimmed;
    }
}
