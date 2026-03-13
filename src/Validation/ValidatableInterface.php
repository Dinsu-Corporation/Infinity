<?php

declare(strict_types=1);

namespace Dinsu\Infinity\Validation;

interface ValidatableInterface
{
    /**
     * Return an array of validation errors. Empty array means valid.
     */
    public function validate(): array;
}
