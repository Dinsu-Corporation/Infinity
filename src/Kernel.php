<?php

declare(strict_types=1);

namespace Dinsu\Infinity;

use Throwable;
use ReflectionClass;
use ReflectionAttribute;
use ReflectionMethod;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Dinsu\Infinity\Container\Container;
use Dinsu\Infinity\Config\ConfigLoader;
use Dinsu\Infinity\Attribute\RestController;
use Dinsu\Infinity\Attribute\Component;
use Dinsu\Infinity\Attribute\Intermediary;
use Dinsu\Infinity\Attribute\Handle;
use Dinsu\Infinity\Attribute\Blueprint;
use Dinsu\Infinity\Attribute\Define;
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
    private array $exceptionHandlers = [];
    private readonly string $env;

    public function __construct(string $env = 'Dev')
    {
        $this->env = $env;
        $this->router = new Router();
        $this->container = new Container();

        $rootPath = dirname(__DIR__, 1);
        $configPath = $rootPath . '/config';

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
            $this->scanForComponents(__DIR__ . '/Domain', 'Dinsu\Infinity\Domain');
        }

        $appPath = dirname(__DIR__, 1) . '/app';

        $foldersToScan = [
            'Controller' => 'App\Controller',
            'Service' => 'App\Service',
            'Repository' => 'App\Repository',
            'Utility' => 'App\Utility',
            'Config' => 'App\Config'
        ];

        foreach ($foldersToScan as $folder => $namespace) {
            $fullPath = $appPath . '/' . $folder;
            if (is_dir($fullPath)) {
                $this->scanForComponents($fullPath, $namespace);
            }
        }
    }

    private function isSystemRouteEnabled(): bool
    {
        $infinityConfig = $this->container->getParam('infinity');
        return is_array($infinityConfig) &&
               isset($infinityConfig['enable_system_routes']) &&
               $infinityConfig['enable_system_routes'] === true;
    }

    private function scanForComponents(string $path, string $namespace): void
    {
        if (!is_dir($path)) {
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

            $this->registerComponentIfMarked($reflection);
            $this->registerRoutesFromAttributes($reflection);
            $this->registerExceptionHandlers($reflection);
            $this->registerDefinitions($reflection);
        }
    }

    private function registerComponentIfMarked(ReflectionClass $reflection): void
    {
        $attributes = $reflection->getAttributes(Component::class, ReflectionAttribute::IS_INSTANCEOF);

        if (!empty($attributes)) {
            $this->container->get($reflection->getName());
        }
    }

    private function registerRoutesFromAttributes(ReflectionClass $reflection): void
    {
        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $attribute) {
                $routeAttr = $attribute->newInstance();

                $this->register(
                    $routeAttr->method,
                    $routeAttr->path,
                    [$this->container->get($reflection->getName()), $method->getName()]
                );
            }
        }
    }

    private function registerExceptionHandlers(ReflectionClass $reflection): void
    {
        if (empty($reflection->getAttributes(Intermediary::class))) {
            return;
        }

        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(Handle::class);
            foreach ($attributes as $attribute) {
                $handlerAttr = $attribute->newInstance();
                $this->exceptionHandlers[$handlerAttr->exceptionClass] = [
                    $this->container->get($reflection->getName()),
                    $method->getName()
                ];
            }
        }
    }

    private function registerDefinitions(ReflectionClass $reflection): void
    {
        if (empty($reflection->getAttributes(Blueprint::class))) {
            return;
        }

        $blueprintInstance = $this->container->get($reflection->getName());

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(Define::class);
            if (empty($attributes)) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (!$returnType || $returnType->isBuiltin()) {
                continue;
            }

            $instance = $this->container->resolveMethod($blueprintInstance, $method->getName());
            $this->container->set($returnType->getName(), $instance);
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

            $coreHandler = $route->handler();
            $pipeline = $this->buildPipeline($coreHandler);

            $result = $pipeline->handle($request);

            if ($this->shouldConvertToRestControllerResponse($coreHandler, $result)) {
                return Response::json($result);
            }

            return $result instanceof Response ? $result : Response::json($result);

        } catch (Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    private function handleException(Throwable $e, Request $request): Response
    {
        foreach ($this->exceptionHandlers as $exceptionClass => $handler) {
            if ($e instanceof $exceptionClass) {
                $result = call_user_func($handler, $e, $request);

                $response = $result instanceof Response ? $result : Response::json($result);

                if ($e instanceof MethodNotAllowedException && !isset($response->headers()['allow'])) {
                    $response = $response->withHeader('Allow', implode(', ', $e->allowedMethods()));
                }

                return $response;
            }
        }

        $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

        return Response::json([
            'error' => 'Internal Server Error',
            'message' => $this->env === 'Dev' ? $e->getMessage() : 'An unexpected error occurred.'
        ], $status);
    }

    private function shouldConvertToRestControllerResponse(RequestHandlerInterface $handler, mixed $result): bool
    {
        if (!$handler instanceof CallableHandler || $result instanceof Response) {
            return false;
        }

        $callback = $handler->getCallable();
        if (!is_array($callback) || !isset($callback[0])) {
            return false;
        }

        $reflection = new ReflectionClass($callback[0]);
        $restControllerAttr = $reflection->getAttributes(RestController::class);

        return !empty($restControllerAttr);
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
