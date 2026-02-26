<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Routing;

use Dinsu\Infinity\Routing\Exception\MethodNotAllowedException;
use Dinsu\Infinity\Routing\Exception\NotFoundException;

/**
 * Router
 * * The central registry and resolution engine for application routes.
 * Supports dynamic placeholders (e.g., /user/{id}) via Regex mapping.
 */
final class Router
{
    /** @var Route[] */
    private array $routes = [];

    /**
     * Adds a Route object to the internal registry.
     */
    public function register(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * Resolves an incoming request to a specific Route and its parameters.
     * * @param string $method The HTTP verb (GET, POST, etc.)
     * @param string $path The URI path to match.
     * @return array{0: Route, 1: array<string, string>} A tuple of the Route and its dynamic params.
     * * @throws MethodNotAllowedException If the path exists but the method is invalid.
     * @throws NotFoundException If no route matches the path.
     */
    public function resolve(string $method, string $path): array
    {
        $allowedMethods = [];
        $upperMethod = strtoupper($method);

        foreach ($this->routes as $route) {
            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route->path());

            if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                if ($route->method() === $upperMethod) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    return [$route, $params];
                }

                $allowedMethods[] = $route->method();
            }
        }

        if (!empty($allowedMethods)) {
            throw new MethodNotAllowedException(array_unique($allowedMethods));
        }

        throw new NotFoundException("No route registered for [{$path}]");
    }
}
