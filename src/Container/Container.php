<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Container;

use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use RuntimeException;

final class Container
{
    private array $instances = [];
    private array $parameters = [];
    private array $resolving = [];

    public function setParam(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function getParam(string $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }

    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function set(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new RuntimeException("Circular dependency detected while resolving: {$id}");
        }

        $this->resolving[$id] = true;

        try {
            $reflection = new ReflectionClass($id);

            if (!$reflection->isInstantiable()) {
                throw new RuntimeException("Class {$id} is not instantiable (Abstract or Interface).");
            }

            $constructor = $reflection->getConstructor();

            if (!$constructor) {
                return $this->instances[$id] = new $id();
            }

            $dependencies = $this->resolveDependencies($constructor);

            return $this->instances[$id] = $reflection->newInstanceArgs($dependencies);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    public function resolveMethod(object $instance, string $method): mixed
    {
        $reflectionMethod = new ReflectionMethod($instance, $method);
        $dependencies = $this->resolveDependencies($reflectionMethod);

        return $reflectionMethod->invokeArgs($instance, $dependencies);
    }

    private function resolveDependencies(ReflectionMethod $method): array
    {
        $dependencies = [];
        $className = $method->getDeclaringClass()->getName();

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            $paramName = $param->getName();

            if (!$type || $type->isBuiltin()) {
                if (array_key_exists($paramName, $this->parameters)) {
                    $dependencies[] = $this->parameters[$paramName];
                    continue;
                }

                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                    continue;
                }

                throw new RuntimeException("Unresolvable parameter [\${$paramName}] in {$className}::{$method->getName()}");
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $dependencies;
    }
}
