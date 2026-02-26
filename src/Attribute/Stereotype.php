<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Component {}

#[Attribute(Attribute::TARGET_CLASS)]
final class RestController extends Component {}

#[Attribute(Attribute::TARGET_CLASS)]
final class Service extends Component {}

#[Attribute(Attribute::TARGET_CLASS)]
final class Repository extends Component {}

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Payload {}

#[Attribute(Attribute::TARGET_CLASS)]
final class Intermediary extends Component {}

#[Attribute(Attribute::TARGET_METHOD)]
final class Handle
{
    public function __construct(
        public string $exceptionClass
    ) {}
}

#[Attribute(Attribute::TARGET_CLASS)]
final class Blueprint extends Component {}

#[Attribute(Attribute::TARGET_METHOD)]
final class Define {}
