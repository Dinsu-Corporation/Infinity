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

        return array_replace_recursive($config, $envConfig);
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
}
