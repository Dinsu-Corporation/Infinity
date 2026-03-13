<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Lifecycle;

interface LifecycleHookInterface
{
    public function onStart(): void;
    public function onStop(): void;
}
