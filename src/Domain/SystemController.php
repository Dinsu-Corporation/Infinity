<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Domain;

use Dinsu\Infinity\Attribute\RestController;
use Dinsu\Infinity\Routing\Attribute\GetMapping;
use Dinsu\Infinity\Http\Request;

#[RestController]
final class SystemController
{
    public function __construct(
        private readonly SystemService $service
    ) {}

    #[GetMapping('/_infinity/home')]
    public function home(Request $request): array
    {
        return $this->service->welcomePayload();
    }

    #[GetMapping('/_infinity/health')]
    public function health(Request $request): array
    {
        return $this->service->healthSnapshot();
    }
}
