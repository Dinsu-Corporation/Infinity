<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Domain;

use Dinsu\Infinity\Http\Request;
use Dinsu\Infinity\Http\Response;
use Dinsu\Infinity\Routing\Attribute\Route;

/**
 * SystemController (Framework Internal)
 * * This controller is part of the Infinity Core. It provides standardized
 * health and status endpoints that are available immediately upon installation.
 */
final class SystemController
{
    public function __construct(
        private readonly SystemService $service
    ) {}

    #[Route(path: '/_infinity/home', method: 'GET')]
    public function home(Request $request): Response
    {
        return Response::json($this->service->welcomePayload());
    }

    #[Route(path: '/_infinity/health', method: 'GET')]
    public function health(Request $request): Response
    {
        return Response::json($this->service->healthSnapshot());
    }
}
