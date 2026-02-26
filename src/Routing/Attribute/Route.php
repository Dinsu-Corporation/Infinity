<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Routing\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        public string $path,
        public string $method = 'GET'
    ) {}
}

#[Attribute(Attribute::TARGET_METHOD)]
final class GetMapping extends Route
{
    public function __construct(string $path)
    {
        parent::__construct($path, 'GET');
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
final class PostMapping extends Route
{
    public function __construct(string $path)
    {
        parent::__construct($path, 'POST');
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
final class PutMapping extends Route
{
    public function __construct(string $path)
    {
        parent::__construct($path, 'PUT');
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
final class PatchMapping extends Route
{
    public function __construct(string $path)
    {
        parent::__construct($path, 'PATCH');
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
final class DeleteMapping extends Route
{
    public function __construct(string $path)
    {
        parent::__construct($path, 'DELETE');
    }
}
