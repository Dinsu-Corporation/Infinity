<?php

declare(strict_types=1);

namespace Dinsu\Infinity;

use Throwable;
use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Dinsu\Infinity\Container\Container;
use Dinsu\Infinity\Config\ConfigLoader;
use Dinsu\Infinity\Execution\{
    RequestHandlerInterface,
    CallableHandler,
    MiddlewareInterface,
    MiddlewareStack
};
use Dinsu\Infinity\Http\{Request, Response};
use Dinsu\Infinity\Routing\{
    Router,
    Route,
    Exception\NotFoundException,
    Exception\MethodNotAllowedException,
    Attribute\Route as RouteAttribute
};

final class Kernel
{
    private readonly Router $router;
    private readonly Container $container;
    private array $middlewares = [];
    private readonly string $env;

    public function __construct(string $env = 'Dev')
    {
        $this->env = $env;
        $this->router = new Router();
        $this->container = new Container();

        $rootPath = dirname(__DIR__, 1);
        $configPath = $rootPath . '/config';

        # DEBUG: Verify path in logs
        file_put_contents('php://stderr', "Kernel Root: $rootPath\n");
        file_put_contents('php://stderr', "Config Path: $configPath\n");

        try {
            $config = ConfigLoader::load($configPath, $env);
            foreach ($config as $key => $value) {
                $this->container->setParam($key, $value);
            }
        } catch (Throwable $e) {
            file_put_contents('php://stderr', "Config Load Error: " . $e->getMessage() . "\n");
        }

        $this->boot();
    }

    private function boot(): void
    {
        if ($this->isSystemRouteEnabled()) {
            file_put_contents('php://stderr', "System routes ENABLED. Scanning...\n");
            $this->scanForRoutes(__DIR__ . '/Domain', 'Dinsu\Infinity\Domain');
        } else {
            file_put_contents('php://stderr', "System routes DISABLED.\n");
        }

        $appControllerPath = dirname(__DIR__, 1) . '/app/Controller';
        if (is_dir($appControllerPath)) {
            $this->scanForRoutes($appControllerPath, 'App\Controller');
        }
    }

    private function isSystemRouteEnabled(): bool
    {
        $infinityConfig = $this->container->getParam('infinity');
        return is_array($infinityConfig) &&
               isset($infinityConfig['enable_system_routes']) &&
               $infinityConfig['enable_system_routes'] === true;
    }

    private function scanForRoutes(string $path, string $namespace): void
    {
        if (!is_dir($path)) {
            file_put_contents('php://stderr', "Directory not found: $path\n");
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($path));
            $className = $namespace . str_replace(['/', '.php'], ['\\', ''], $relativePath);

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(RouteAttribute::class);
                foreach ($attributes as $attribute) {
                    $routeAttr = $attribute->newInstance();
                    file_put_contents('php://stderr', "Registered: [{$routeAttr->method}] {$routeAttr->path}\n");

                    $this->register(
                        $routeAttr->method,
                        $routeAttr->path,
                        [$this->container->get($className), $method->getName()]
                    );
                }
            }
        }
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function register(string $method, string $path, mixed $handlerInput): self
    {
        $handler = match(true) {
            $handlerInput instanceof RequestHandlerInterface => $handlerInput,
            is_array($handlerInput) && is_object($handlerInput[0]) => new CallableHandler($handlerInput),
            is_string($handlerInput) && class_exists($handlerInput) => $this->container->get($handlerInput),
            default => new CallableHandler($handlerInput)
        };

        $this->router->register(new Route($method, $path, $handler));
        return $this;
    }

    public function run(Request $request): Response
    {
        try {
            [$route, $params] = $this->router->resolve($request->method(), $request->path());
            $request = $request->withRouteParams($params);
            return $this->buildPipeline($route->handler())->handle($request);
        } catch (NotFoundException $e) {
            return Response::json(['error' => 'Not Found', 'path' => $request->path()], 404);
        } catch (MethodNotAllowedException $e) {
            return Response::json(['error' => 'Method Not Allowed'], 405)
                ->withHeader('Allow', implode(', ', $e->allowedMethods()));
        } catch (Throwable $e) {
            return Response::json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function buildPipeline(RequestHandlerInterface $coreHandler): RequestHandlerInterface
    {
        $pipeline = $coreHandler;
        foreach (array_reverse($this->middlewares) as $middleware) {
            $pipeline = new MiddlewareStack($middleware, $pipeline);
        }
        return $pipeline;
    }

    public function get(string $path, mixed $handler): self { return $this->register('GET', $path, $handler); }
    public function post(string $path, mixed $handler): self { return $this->register('POST', $path, $handler); }
}
