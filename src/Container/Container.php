<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Container;

use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * Infinity IoC Container
 * A reflection-based dependency injection container supporting
 * recursive resolution and parameter injection.
 */
final class Container
{
    private array $instances = [];
    private array $parameters = [];
    private array $resolving = [];

    /**
     * Sets a primitive parameter (strings, ints, etc.) for injection.
     */
    public function setParam(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Resolves a class and its dependencies recursively.
     * * @throws RuntimeException|ReflectionException
     */
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

            $dependencies = [];
            foreach ($constructor->getParameters() as $param) {
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

                    throw new RuntimeException("Unresolvable parameter [\${$paramName}] in class {$id}");
                }

                // Handle class/interface dependencies
                /** @psalm-suppress UndefinedClass */
                $dependencies[] = $this->get($type->getName());
            }

            return $this->instances[$id] = $reflection->newInstanceArgs($dependencies);
        } finally {
            unset($this->resolving[$id]);
        }
    }
}
