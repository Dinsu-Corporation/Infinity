<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Execution;

use Closure;
use ReflectionMethod;
use ReflectionClass;
use ReflectionParameter;
use Dinsu\Infinity\Http\Request;
use Dinsu\Infinity\Attribute\Payload;
use Dinsu\Infinity\Validation\ValidatableInterface;
use Dinsu\Infinity\Validation\ValidationException;

final class CallableHandler implements RequestHandlerInterface
{
    private readonly Closure $handler;
    private readonly mixed $originalCallable;

    public function __construct(callable $handler)
    {
        $this->originalCallable = $handler;
        $this->handler = Closure::fromCallable($handler);
    }

    public function handle(Request $request): mixed
    {
        if (!is_array($this->originalCallable)) {
            return ($this->handler)($request);
        }

        [$controller, $methodName] = $this->originalCallable;
        $reflection = new ReflectionMethod($controller, $methodName);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $arguments[] = $this->resolveArgument($parameter, $request);
        }

        return $reflection->invokeArgs($controller, $arguments);
    }

    private function resolveArgument(ReflectionParameter $parameter, Request $request): mixed
    {
        $type = $parameter->getType();
        $typeName = $type ? $type->getName() : null;

        if ($typeName === Request::class) {
            return $request;
        }

        $payloadAttr = $parameter->getAttributes(Payload::class);
        if (!empty($payloadAttr) && $typeName && class_exists($typeName)) {
            return $this->mapDto($typeName, $request->parsedBody() ?? []);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return null;
    }

    private function mapDto(string $dtoClass, mixed $data): object
    {
        $reflection = new ReflectionClass($dtoClass);
        $dto = $reflection->newInstanceWithoutConstructor();

        $payloadData = is_array($data) ? $data : [];

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $payloadData)) {
                $property->setAccessible(true);
                $property->setValue($dto, $payloadData[$name]);
            }
        }

        if ($dto instanceof ValidatableInterface) {
            $errors = $dto->validate();
            if (!empty($errors)) {
                throw new ValidationException($errors);
            }
        }

        return $dto;
    }

    public function getCallable(): mixed
    {
        return $this->originalCallable;
    }
}
