<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Routing\Attribute;

use Attribute;

/**
 * This is the Attribute used for DISCOVERY.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Route
{
    public function __construct(
        public string $path,
        public string $method = 'GET'
    ) {}
}
