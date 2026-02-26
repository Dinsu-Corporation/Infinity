<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dinsu\Infinity\Kernel;
use Dinsu\Infinity\Http\Request;

$env = getenv('APP_ENV') ?: 'Dev';

$kernel = new Kernel($env);

$request = Request::fromGlobals();
$response = $kernel->run($request);

$response->send();
