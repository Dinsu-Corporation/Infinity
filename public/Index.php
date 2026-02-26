<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dinsu\Infinity\Kernel;
use Dinsu\Infinity\Http\Request;

$env = getenv('APP_ENV') ?: 'Dev';
$kernel = new Kernel($env);

$handler = static function () use ($kernel) {
    $request = Request::fromGlobals();
    $response = $kernel->run($request);
    $response->send();
};

if (function_exists('frankenphp_handle_request')) {
    while (frankenphp_handle_request($handler));
} else {
    $handler();
}
