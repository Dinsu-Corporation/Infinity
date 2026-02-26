<?php

declare(strict_types=1);

namespace Dinsu\Infinity;

use Throwable;
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
    Exception\MethodNotAllowedException
};

/**
 * Kernel
 * The heart of the Infinity Framework. Orchestrates the request/response
 * lifecycle by managing configuration, dependency injection, and routing.
 */
final class Kernel
{
    private readonly Router $router;
    private readonly Container $container;
    private array $middlewares = [];

    public function __construct(string $env = 'Dev')
    {
        $this->router = new Router();
        $this->container = new Container();

        $configPath = __DIR__ . '/../config';
        $config = ConfigLoader::load($configPath, $env);

        foreach ($config as $key => $value) {
            $this->container->setParam($key, $value);
        }
    }

    /**
     * Adds a global middleware to the execution pipeline.
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Registers a route with the router.
     * Automatically resolves handlers from the container if a class name is provided.
     */
    public function register(string $method, string $path, mixed $handlerInput): self
    {
        $handler = match(true) {
            $handlerInput instanceof RequestHandlerInterface => $handlerInput,

            is_string($handlerInput) && class_exists($handlerInput) => $this->container->get($handlerInput),

            default => new CallableHandler($handlerInput)
        };

        $this->router->register(new Route($method, $path, $handler));
        return $this;
    }

    /**
     * Dispatches the request through the middleware stack to the matched route.
     */
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
                'message' => $e->getMessage(),
                'trace' => ($this->container->get('env') === 'Dev') ? $e->getTrace() : []
            ], 500);
        }
    }

    /**
     * Wraps the core handler in layers of middleware (The Onion).
     */
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
